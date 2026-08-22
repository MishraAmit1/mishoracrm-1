<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Models\WhatsappSetting;
use App\Services\NotificationService;
use App\Services\TicketNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findTicket(int|string $id): Ticket
    {
        return Ticket::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Ticket::with(['contact', 'assignee'])
            ->where('tenant_id', $this->tenantId())
            ->latest();

        $status = $request->get('status', 'open');

        match ($status) {
            'open'        => $query->where('status', 'open'),
            'in_progress' => $query->where('status', 'in_progress'),
            'resolved'    => $query->where('status', 'resolved'),
            'closed'      => $query->where('status', 'closed'),
            'mine'        => $query->mine(auth()->id()),
            default       => null,
        };

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->paginate(20)->withQueryString();

        $base = Ticket::where('tenant_id', $this->tenantId());
        $counts = [
            'open'        => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'resolved'    => (clone $base)->where('status', 'resolved')->count(),
            'closed'      => (clone $base)->where('status', 'closed')->count(),
            'mine'        => (clone $base)->mine(auth()->id())->count(),
        ];

        $tenant = auth()->user()->tenant;

        $confirmPrefs = [
            'email'         => $tenant->wantsTicketConfirmation('email'),
            'whatsapp'      => $tenant->wantsTicketConfirmation('whatsapp'),
            'email_subject' => $tenant->ticketConfirmationTemplate('email_subject') ?? TicketNotificationService::DEFAULT_EMAIL_SUBJECT,
            'email_body'    => $tenant->ticketConfirmationTemplate('email_body') ?? TicketNotificationService::DEFAULT_EMAIL_BODY,
            'whatsapp_body' => $tenant->ticketConfirmationTemplate('whatsapp_body') ?? TicketNotificationService::DEFAULT_WHATSAPP_BODY,
        ];
        $whatsappConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        return view('tenant.tickets.index', compact('tickets', 'counts', 'status', 'tenant', 'confirmPrefs', 'whatsappConnected'));
    }

    // ── Update ticket-confirmation channel/template preferences ─────
    public function updatePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'email'         => ['nullable', 'boolean'],
            'whatsapp'      => ['nullable', 'boolean'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body'    => ['nullable', 'string', 'max:5000'],
            'whatsapp_body' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant   = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];

        $settings['preferences']['ticket_confirmation_email']         = $request->boolean('email');
        $settings['preferences']['ticket_confirmation_whatsapp']      = $request->boolean('whatsapp');
        $settings['preferences']['ticket_confirmation_email_subject'] = $request->email_subject;
        $settings['preferences']['ticket_confirmation_email_body']    = $request->email_body;
        $settings['preferences']['ticket_confirmation_whatsapp_body'] = $request->whatsapp_body;

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Ticket confirmation preferences updated.');
    }

    // ── Send the current (possibly unsaved) email template to yourself ──
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email_subject' => ['required', 'string', 'max:255'],
            'email_body'    => ['required', 'string', 'max:5000'],
        ]);

        $user = auth()->user();
        $sent = TicketNotificationService::sendTestEmail(
            $this->tenantId(),
            $user->email,
            $user->name,
            $request->email_subject,
            $request->email_body
        );

        return back()->with($sent ? 'success' : 'error', $sent ? 'Test email sent to ' . $user->email : 'Failed to send test email.');
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'phone']);
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name']);

        return view('tenant.tickets.create', compact('contacts', 'services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_id'  => ['required', 'integer', 'exists:contacts,id'],
            'service_id'  => ['nullable', 'integer', 'exists:services,id'],
            'subject'     => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority'    => ['required', 'in:low,medium,high,urgent'],
            'notify_via'  => ['nullable', 'in:default,both,email,whatsapp,none'],
        ]);

        $ticket = Ticket::create([
            'tenant_id'     => $this->tenantId(),
            'ticket_number' => Ticket::generateNumber($this->tenantId()),
            'contact_id'    => $data['contact_id'],
            'service_id'    => $data['service_id'] ?? null,
            'subject'       => $data['subject'],
            'description'   => $data['description'] ?? null,
            'priority'      => $data['priority'],
            'status'        => 'open',
            'source'        => 'manual',
            'created_by'    => auth()->id(),
        ]);

        $notifyVia = $data['notify_via'] ?? 'default';
        $channels = match ($notifyVia) {
            'email'    => ['email'],
            'whatsapp' => ['whatsapp'],
            'both'     => ['email', 'whatsapp'],
            'default'  => null, // use the tenant's saved channel preference
            default    => [],
        };

        if ($notifyVia !== 'none') {
            TicketNotificationService::sendConfirmation($ticket->fresh(['contact', 'tenant']), $channels);
        }

        return redirect()->route('tenant.tickets.show', $ticket->id)
            ->with('success', "Ticket {$ticket->ticket_number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $ticket = $this->findTicket($id);
        $ticket->load(['contact', 'service', 'assignee', 'attachments', 'replies.user']);
        $staff = User::withoutGlobalScopes()->where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('tenant.tickets.show', compact('ticket', 'staff'));
    }

    // ── Reply ─────────────────────────────────────────────────────
    public function reply(Request $request, int|string $id): RedirectResponse
    {
        $ticket = $this->findTicket($id);

        $data = $request->validate([
            'body'             => ['required', 'string', 'max:5000'],
            'is_internal_note' => ['nullable', 'boolean'],
        ]);

        $isInternal = $request->boolean('is_internal_note');

        $ticket->replies()->create([
            'tenant_id'         => $this->tenantId(),
            'user_id'           => auth()->id(),
            'is_customer_reply' => false,
            'is_internal_note'  => $isInternal,
            'body'              => $data['body'],
        ]);

        if (!$isInternal) {
            TicketNotificationService::sendReply($ticket->fresh(['contact', 'tenant']), $data['body']);
        }

        return back()->with('success', $isInternal ? 'Internal note added.' : 'Reply sent.');
    }

    // ── Status / Priority / Assignment ───────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:open,in_progress,resolved,closed']]);

        $ticket = $this->findTicket($id);
        $ticket->update([
            'status'      => $request->status,
            'resolved_at' => in_array($request->status, ['resolved', 'closed']) ? now() : null,
        ]);

        return back()->with('success', 'Ticket marked ' . (Ticket::statuses()[$request->status] ?? $request->status) . '.');
    }

    public function updatePriority(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['priority' => ['required', 'in:low,medium,high,urgent']]);

        $this->findTicket($id)->update(['priority' => $request->priority]);

        return back()->with('success', 'Priority updated.');
    }

    public function assign(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);

        $ticket = $this->findTicket($id);
        $ticket->update(['assigned_to' => $request->assigned_to]);

        if ($request->assigned_to && (int) $request->assigned_to !== auth()->id()) {
            $assignee = User::withoutGlobalScopes()->find($request->assigned_to);
            if ($assignee) {
                NotificationService::notify(
                    'ticket.assigned',
                    $assignee,
                    ['subject' => $ticket->subject],
                    auth()->user(),
                    route('tenant.tickets.show', $ticket->id),
                    $ticket
                );
            }
        }

        return back()->with('success', 'Ticket assigned.');
    }

    // ── Attachments ───────────────────────────────────────────────
    public function storeAttachment(Request $request, int|string $id): RedirectResponse
    {
        $ticket = $this->findTicket($id);

        $request->validate([
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->storeAs("tickets/{$this->tenantId()}/{$ticket->id}", Str::uuid() . '.' . $file->getClientOriginalExtension(), 'public');

            TicketAttachment::create([
                'tenant_id'     => $this->tenantId(),
                'ticket_id'     => $ticket->id,
                'uploaded_by'   => auth()->id(),
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Attachment(s) uploaded.');
    }

    public function destroyAttachment(int|string $ticketId, int|string $attachmentId): RedirectResponse
    {
        $ticket     = $this->findTicket($ticketId);
        $attachment = TicketAttachment::where('id', $attachmentId)->where('ticket_id', $ticket->id)->firstOrFail();

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment deleted.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $this->findTicket($id)->delete();

        return redirect()->route('tenant.tickets.index')
            ->with('success', 'Ticket deleted.');
    }
}
