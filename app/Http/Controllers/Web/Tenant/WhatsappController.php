<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // Returns a ready-to-use sender for this tenant, or null if the tenant
    // hasn't connected the WhatsApp Cloud API yet (Settings > WhatsApp API).
    private function connectedService(): ?WhatsappChatbotService
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->exists || !$settings->is_connected) {
            return null;
        }

        return WhatsappChatbotService::forTenant($this->tenantId());
    }

    // ── Index — dashboard ─────────────────────────────────────────
    public function index(): View
    {
        $stats = [
            'total_sent'  => WhatsappLog::where('status', 'sent')->count(),
            'today'       => WhatsappLog::whereDate('created_at', today())->count(),
            'this_month'  => WhatsappLog::whereMonth('created_at', now()->month)->count(),
            'failed'      => WhatsappLog::where('status', 'failed')->count(),
            'templates'   => WhatsappTemplate::where('is_active', true)->count(),
        ];

        $recentLogs = WhatsappLog::with(['sentBy', 'template'])
            ->latest()->limit(5)->get();

        return view('tenant.whatsapp.index', compact('stats', 'recentLogs'));
    }

    // ── Templates — list ──────────────────────────────────────────
    public function templates(): View
    {
        $templates  = WhatsappTemplate::withCount('logs')->latest()->paginate(12);
        $categories = WhatsappTemplate::categories();

        return view('tenant.whatsapp.templates', compact('templates', 'categories'));
    }

    // ── Template — store ──────────────────────────────────────────
    public function storeTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(WhatsappTemplate::categories()))],
        ]);

        WhatsappTemplate::create([
            'tenant_id' => $this->tenantId(),
            'name'      => $request->name,
            'body'      => $request->body,
            'category'  => $request->category,
            'is_active' => true,
        ]);

        return back()->with('success', 'Template created successfully.');
    }

    // ── Template — update ─────────────────────────────────────────
    public function updateTemplate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(WhatsappTemplate::categories()))],
        ]);

        $template = WhatsappTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $template->update($request->only('name', 'body', 'category'));

        return back()->with('success', 'Template updated.');
    }

    // ── Template — delete ─────────────────────────────────────────
    public function deleteTemplate(int $id): RedirectResponse
    {
        WhatsappTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->delete();

        return back()->with('success', 'Template deleted.');
    }

    // ── Send — single ─────────────────────────────────────────────
    public function sendForm(Request $request): View
    {
        $templates = WhatsappTemplate::where('is_active', true)->orderBy('name')->get();
        $leads     = Lead::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'phone']);

        // Pre-fill if came from lead/contact
        $lead    = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $isConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        return view('tenant.whatsapp.send', compact(
            'templates', 'leads', 'contacts', 'lead', 'contact', 'isConnected'
        ));
    }

    // ── Send — process single ─────────────────────────────────────
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'to_phone'    => ['required', 'string'],
            'to_name'     => ['nullable', 'string'],
            'message'     => ['required', 'string'],
            'lead_id'     => ['nullable', 'exists:leads,id'],
            'contact_id'  => ['nullable', 'exists:contacts,id'],
            'template_id' => ['nullable', 'exists:whatsapp_templates,id'],
            'attachment'  => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);

        $service = $this->connectedService();
        if (!$service) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        $waId = preg_replace('/[^0-9]/', '', $request->to_phone);

        [$ok, $error, $mediaType, $mediaId, $attachmentName] = $this->deliver($service, $waId, $request->message, $request->file('attachment'));

        WhatsappLog::create([
            'tenant_id'       => $this->tenantId(),
            'template_id'     => $request->template_id,
            'lead_id'         => $request->lead_id,
            'contact_id'      => $request->contact_id,
            'sent_by'         => auth()->id(),
            'to_phone'        => $request->to_phone,
            'to_name'         => $request->to_name,
            'message'         => $request->message,
            'status'          => $ok ? 'sent' : 'failed',
            'error_message'   => $error,
            'media_type'      => $mediaType,
            'media_id'        => $mediaId,
            'attachment_name' => $attachmentName,
            'sent_at'         => now(),
        ]);

        if (!$ok) {
            return back()->with('error', 'Failed to send WhatsApp message' . ($error ? ": {$error}" : '.'));
        }

        return redirect()->route('tenant.whatsapp.logs')->with('success', "Message sent to {$request->to_phone}.");
    }

    // Shared single-recipient delivery used by both send() and sendBulk() —
    // uploads the attachment (once, by the caller) or reuses an already
    // uploaded media id, then sends text or media accordingly.
    // Returns [ok, error, mediaType, mediaId, attachmentName].
    private function deliver(WhatsappChatbotService $service, string $waId, string $message, $file = null, ?string $preUploadedMediaId = null, ?string $preMediaType = null, ?string $preAttachmentName = null): array
    {
        if ($preUploadedMediaId) {
            $ok = $service->sendMediaMessage($waId, $preUploadedMediaId, $preMediaType, $message, $preAttachmentName);
            return [$ok, $ok ? null : 'WhatsApp API rejected the media message.', $preMediaType, $preUploadedMediaId, $preAttachmentName];
        }

        if ($file) {
            $mediaType = WhatsappChatbotService::mediaTypeForMime($file->getMimeType());
            $attachmentName = $file->getClientOriginalName();
            $mediaId = $service->uploadMedia($file->getRealPath(), $file->getMimeType());

            if (!$mediaId) {
                return [false, 'Media upload failed.', $mediaType, null, $attachmentName];
            }

            $ok = $service->sendMediaMessage($waId, $mediaId, $mediaType, $message, $attachmentName);
            return [$ok, $ok ? null : 'WhatsApp API rejected the media message.', $mediaType, $mediaId, $attachmentName];
        }

        $ok = $service->sendMessage($waId, $message);
        return [$ok, $ok ? null : 'WhatsApp API rejected the message.', null, null, null];
    }

    // ── Bulk send form ────────────────────────────────────────────
    public function bulkForm(): View
    {
        $templates  = WhatsappTemplate::where('is_active', true)->orderBy('name')->get();
        $leads      = Lead::orderBy('name')->get(['id', 'name', 'phone', 'source', 'status']);
        $contacts   = Contact::orderBy('name')->get(['id', 'name', 'phone', 'company']);

        $isConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        return view('tenant.whatsapp.bulk', compact('templates', 'leads', 'contacts', 'isConnected'));
    }

    // ── Bulk send — process ───────────────────────────────────────
    public function sendBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'recipients'  => ['required', 'array', 'min:1'],
            'message'     => ['required', 'string'],
            'template_id' => ['nullable', 'exists:whatsapp_templates,id'],
            'type'        => ['required', 'in:leads,contacts'],
            'attachment'  => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);

        $service = $this->connectedService();
        if (!$service) {
            return back()->with('error', 'WhatsApp is not connected. Please configure it in WhatsApp API Settings first.');
        }

        // Upload the attachment once — the same media id is reused for every recipient below.
        $mediaId = $mediaType = $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mediaType = WhatsappChatbotService::mediaTypeForMime($file->getMimeType());
            $attachmentName = $file->getClientOriginalName();
            $mediaId = $service->uploadMedia($file->getRealPath(), $file->getMimeType());

            if (!$mediaId) {
                return back()->with('error', 'Media upload failed — bulk send cancelled.');
            }
        }

        $bulkId = Str::uuid();
        $today  = now()->format('d M Y');

        $sent = 0;
        $failed = 0;

        foreach ($request->recipients as $id) {
            $record = $request->type === 'leads'
                ? Lead::find($id)
                : Contact::find($id);

            if (!$record || !$record->phone) continue;

            $waId = preg_replace('/[^0-9]/', '', $record->phone);

            // Replace {{variables}} in the actual submitted message per recipient
            $message = WhatsappTemplate::substituteVariables($request->message, [
                'name'       => $record->name ?? '',
                'company'    => $record->company ?? '',
                'phone'      => $record->phone ?? '',
                'email'      => $record->email ?? '',
                'amount'     => $record->lead_value ?? '',
                'date'       => $today,
                'business'   => auth()->user()->tenant->name,
                'agent_name' => auth()->user()->name,
            ]);

            [$ok, $error] = $this->deliver($service, $waId, $message, null, $mediaId, $mediaType, $attachmentName);
            $ok ? $sent++ : $failed++;

            WhatsappLog::create([
                'tenant_id'       => $this->tenantId(),
                'template_id'     => $request->template_id,
                'lead_id'         => $request->type === 'leads' ? $id : null,
                'contact_id'      => $request->type === 'contacts' ? $id : null,
                'sent_by'         => auth()->id(),
                'to_phone'        => $record->phone,
                'to_name'         => $record->name,
                'message'         => $message,
                'status'          => $ok ? 'sent' : 'failed',
                'error_message'   => $error,
                'media_type'      => $mediaType,
                'media_id'        => $mediaId,
                'attachment_name' => $attachmentName,
                'is_bulk'         => true,
                'bulk_id'         => $bulkId,
                'sent_at'         => now(),
            ]);
        }

        return redirect()
            ->route('tenant.whatsapp.logs')
            ->with('success', "{$sent} messages sent." . ($failed > 0 ? " {$failed} failed." : ''));
    }

    // ── Logs ──────────────────────────────────────────────────────
    public function logs(Request $request): View
    {
        $query = WhatsappLog::with(['sentBy', 'template', 'lead', 'contact'])->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('is_bulk'))  $query->where('is_bulk', $request->is_bulk);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(20)->withQueryString();

        return view('tenant.whatsapp.logs', compact('logs'));
    }

    // ── Preview template (AJAX) ───────────────────────────────────
    public function previewTemplate(Request $request): JsonResponse
    {
        $template = WhatsappTemplate::where('id', $request->template_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $preview = $template->render([
            'name'       => 'Rahul Sharma',
            'company'    => 'Acme Corp',
            'phone'      => '+91 98765 43210',
            'business'   => auth()->user()->tenant->name,
            'agent_name' => auth()->user()->name,
            'date'       => now()->format('d M Y'),
        ]);

        return response()->json(['preview' => $preview]);
    }
}