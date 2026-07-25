<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(): View
    {
        $stats = [
            'total_sent' => EmailLog::where('status', 'sent')->count(),
            'today'      => EmailLog::whereDate('created_at', today())->count(),
            'this_month' => EmailLog::whereMonth('created_at', now()->month)->count(),
            'failed'     => EmailLog::where('status', 'failed')->count(),
            'templates'  => EmailTemplate::where('is_active', true)->count(),
        ];

        $recentLogs = EmailLog::with(['sentBy', 'template'])
            ->latest()->limit(5)->get();

        return view('tenant.email.index', compact('stats', 'recentLogs'));
    }

    // ── Templates ─────────────────────────────────────────────────
    public function templates(): View
    {
        $templates  = EmailTemplate::withCount('logs')->latest()->paginate(12);
        $categories = EmailTemplate::categories();

        return view('tenant.email.templates', compact('templates', 'categories'));
    }

    // ── Template store ────────────────────────────────────────────
    public function storeTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(EmailTemplate::categories()))],
        ]);

        EmailTemplate::create([
            'tenant_id' => $this->tenantId(),
            'name'      => $request->name,
            'subject'   => $request->subject,
            'body'      => $request->body,
            'category'  => $request->category,
            'is_active' => true,
        ]);

        return back()->with('success', 'Email template created.');
    }

    // ── Template update ───────────────────────────────────────────
    public function updateTemplate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['required', 'in:' . implode(',', array_keys(EmailTemplate::categories()))],
        ]);

        EmailTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail()
            ->update($request->only('name', 'subject', 'body', 'category'));

        return back()->with('success', 'Template updated.');
    }

    // ── Template delete ───────────────────────────────────────────
    public function deleteTemplate(int $id): RedirectResponse
    {
        EmailTemplate::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->delete();

        return back()->with('success', 'Template deleted.');
    }

    // ── Send form ─────────────────────────────────────────────────
    public function sendForm(Request $request): View
    {
        $templates = EmailTemplate::where('is_active', true)->orderBy('name')->get();
        $leads     = Lead::whereNotNull('email')->orderBy('name')->get(['id','name','email','phone']);
        $contacts  = Contact::whereNotNull('email')->orderBy('name')->get(['id','name','email','company']);

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)->where('tenant_id', $this->tenantId())->first()
            : null;

        return view('tenant.email.send', compact(
            'templates', 'leads', 'contacts', 'lead', 'contact'
        ));
    }

    // ── Send single ───────────────────────────────────────────────
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'to_email'    => ['required', 'email'],
            'to_name'     => ['nullable', 'string'],
            'subject'     => ['required', 'string'],
            'body'        => ['required', 'string'],
            'lead_id'     => ['nullable', 'exists:leads,id'],
            'contact_id'  => ['nullable', 'exists:contacts,id'],
            'template_id' => ['nullable', 'exists:email_templates,id'],
        ]);

        $status = 'sent';
        $error  = null;

        try {
            $this->dispatchEmail($request->to_email, $request->to_name ?? '', $request->subject, $request->body);
        } catch (\Exception $e) {
            $status = 'failed';
            $error  = $e->getMessage();
        }

        EmailLog::create([
            'tenant_id'   => $this->tenantId(),
            'template_id' => $request->template_id,
            'lead_id'     => $request->lead_id,
            'contact_id'  => $request->contact_id,
            'sent_by'     => auth()->id(),
            'to_email'    => $request->to_email,
            'to_name'     => $request->to_name,
            'subject'     => $request->subject,
            'body'        => $request->body,
            'status'      => $status,
            'error_message' => $error,
            'sent_at'     => now(),
        ]);

        if ($status === 'failed') {
            return back()->with('error', 'Failed to send email: ' . $error);
        }

        return redirect()
            ->route('email.logs')
            ->with('success', "Email sent to {$request->to_email}.");
    }

    // ── Bulk send form ────────────────────────────────────────────
    public function bulkForm(): View
    {
        $templates = EmailTemplate::where('is_active', true)->orderBy('name')->get();
        $leads     = Lead::whereNotNull('email')->orderBy('name')->get(['id','name','email','source','status']);
        $contacts  = Contact::whereNotNull('email')->orderBy('name')->get(['id','name','email','company']);

        return view('tenant.email.bulk', compact('templates', 'leads', 'contacts'));
    }

    // ── Bulk send process ─────────────────────────────────────────
    public function sendBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'recipients'  => ['required', 'array', 'min:1'],
            'subject'     => ['required', 'string'],
            'body'        => ['required', 'string'],
            'template_id' => ['nullable', 'exists:email_templates,id'],
            'type'        => ['required', 'in:leads,contacts'],
        ]);

        $bulkId   = Str::uuid();
        $template = $request->template_id
            ? EmailTemplate::find($request->template_id)
            : null;

        $sent   = 0;
        $failed = 0;

        foreach ($request->recipients as $id) {
            $record = $request->type === 'leads'
                ? Lead::find($id)
                : Contact::find($id);

            if (!$record || !$record->email) continue;

            // Render template
            $rendered = $template
                ? $template->render([
                    'name'       => $record->name,
                    'company'    => $record->company ?? '',
                    'email'      => $record->email,
                    'business'   => auth()->user()->tenant->name,
                    'agent_name' => auth()->user()->name,
                    'date'       => now()->format('d M Y'),
                ])
                : ['subject' => $request->subject, 'body' => $request->body];

            $status = 'sent';
            $error  = null;

            try {
                $this->dispatchEmail($record->email, $record->name ?? '', $rendered['subject'], $rendered['body']);
                $sent++;
            } catch (\Exception $e) {
                $status = 'failed';
                $error  = $e->getMessage();
                $failed++;
            }

            EmailLog::create([
                'tenant_id'     => $this->tenantId(),
                'template_id'   => $request->template_id,
                'lead_id'       => $request->type === 'leads' ? $id : null,
                'contact_id'    => $request->type === 'contacts' ? $id : null,
                'sent_by'       => auth()->id(),
                'to_email'      => $record->email,
                'to_name'       => $record->name,
                'subject'       => $rendered['subject'],
                'body'          => $rendered['body'],
                'status'        => $status,
                'error_message' => $error,
                'is_bulk'       => true,
                'bulk_id'       => $bulkId,
                'sent_at'       => now(),
            ]);
        }

        return redirect()
            ->route('email.logs')
            ->with('success', "{$sent} emails sent." . ($failed > 0 ? " {$failed} failed." : ''));
    }

    // ── Logs ──────────────────────────────────────────────────────
    public function logs(Request $request): View
    {
        $query = EmailLog::with(['sentBy', 'template', 'lead', 'contact'])->latest();

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('is_bulk')) $query->where('is_bulk', $request->is_bulk);
        if ($request->filled('date'))    $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(20)->withQueryString();

        return view('tenant.email.logs', compact('logs'));
    }

    // ── Preview template (AJAX) ───────────────────────────────────
    public function previewTemplate(Request $request): JsonResponse
    {
        $template = EmailTemplate::where('id', $request->template_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $rendered = $template->render([
            'name'       => 'Rahul Sharma',
            'company'    => 'Acme Corp',
            'email'      => 'rahul@acme.com',
            'business'   => auth()->user()->tenant->name,
            'agent_name' => auth()->user()->name,
            'date'       => now()->format('d M Y'),
        ]);

        return response()->json($rendered);
    }

    // ── SMTP Settings — connect your own email ──────────────────────
    public function settings(): View
    {
        $settings = EmailSetting::forTenant($this->tenantId());

        return view('tenant.email.settings', compact('settings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'smtp_host'       => ['required', 'string', 'max:255'],
            'smtp_port'       => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', 'in:tls,ssl,none'],
            'smtp_username'   => ['required', 'string', 'max:255'],
            'smtp_password'   => ['nullable', 'string', 'max:255'],
            'from_address'    => ['required', 'email', 'max:255'],
            'from_name'       => ['required', 'string', 'max:255'],
        ]);

        $settings = EmailSetting::forTenant($this->tenantId());

        if (!$settings->exists && !$request->filled('smtp_password')) {
            return back()->withErrors(['smtp_password' => 'Password is required.'])->withInput();
        }

        $settings->tenant_id       = $this->tenantId();
        $settings->smtp_host       = $request->smtp_host;
        $settings->smtp_port       = $request->smtp_port;
        $settings->smtp_encryption = $request->smtp_encryption === 'none' ? null : $request->smtp_encryption;
        $settings->smtp_username   = $request->smtp_username;
        if ($request->filled('smtp_password')) {
            $settings->smtp_password = $request->smtp_password;
        }
        $settings->from_address = $request->from_address;
        $settings->from_name    = $request->from_name;
        $settings->save();

        return back()->with('success', 'Email (SMTP) settings saved. Ab "Test Connection" karke confirm kar lein.');
    }

    public function testConnection(): JsonResponse
    {
        $settings = EmailSetting::forTenant($this->tenantId());

        if (!$settings->exists || !$settings->smtp_host) {
            return response()->json(['success' => false, 'message' => 'Pehle SMTP settings save karein.']);
        }

        $result = EmailService::test($settings, auth()->user()->email);

        $settings->last_tested_at = now();
        if ($result['success']) {
            $settings->is_connected = true;
        }
        $settings->save();

        return response()->json($result);
    }

    // ── Send an email using tenant's own SMTP if connected, else system mailer ──
    private function dispatchEmail(string $toEmail, string $toName, string $subject, string $html): void
    {
        $settings = EmailSetting::where('tenant_id', $this->tenantId())
            ->where('is_connected', true)
            ->first();

        if ($settings && $settings->smtp_host) {
            EmailService::dispatch($settings, $toEmail, $toName, $subject, $html);
            return;
        }

        Mail::send([], [], function ($mail) use ($toEmail, $toName, $subject, $html) {
            $mail->to($toEmail, $toName)->subject($subject)->html($html);
        });
    }
}