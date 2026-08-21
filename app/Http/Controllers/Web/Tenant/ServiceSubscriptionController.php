<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceSubscription;
use App\Models\WhatsappSetting;
use App\Services\SubscriptionReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceSubscriptionController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findSubscription(int|string $id): ServiceSubscription
    {
        return ServiceSubscription::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = ServiceSubscription::with(['contact', 'service'])
            ->where('tenant_id', $this->tenantId())
            ->latest();

        $tenant       = auth()->user()->tenant;
        $reminderDays = $tenant->subscriptionReminderDays();

        $status = $request->get('status', '');

        match ($status) {
            'active'    => $query->currentlyValid(),
            'expiring'  => $query->expiringSoon($reminderDays),
            'expired'   => $query->expired(),
            'cancelled' => $query->cancelled(),
            default     => null,
        };

        $subscriptions = $query->paginate(20)->withQueryString();

        $base = ServiceSubscription::where('tenant_id', $this->tenantId());
        $counts = [
            'all'       => (clone $base)->count(),
            'active'    => (clone $base)->currentlyValid()->count(),
            'expiring'  => (clone $base)->expiringSoon($reminderDays)->count(),
            'expired'   => (clone $base)->expired()->count(),
            'cancelled' => (clone $base)->cancelled()->count(),
        ];

        $reminderPrefs = [
            'email'         => $tenant->wantsSubscriptionReminder('email'),
            'whatsapp'      => $tenant->wantsSubscriptionReminder('whatsapp'),
            'days'          => $reminderDays,
            'email_subject' => $tenant->subscriptionReminderTemplate('email_subject') ?? SubscriptionReminderService::DEFAULT_EMAIL_SUBJECT,
            'email_body'    => $tenant->subscriptionReminderTemplate('email_body') ?? SubscriptionReminderService::DEFAULT_EMAIL_BODY,
            'whatsapp_body' => $tenant->subscriptionReminderTemplate('whatsapp_body') ?? SubscriptionReminderService::DEFAULT_WHATSAPP_BODY,
        ];
        $whatsappConnected = WhatsappSetting::forTenant($this->tenantId())->is_connected;

        return view('tenant.subscriptions.index', compact('subscriptions', 'counts', 'status', 'reminderPrefs', 'whatsappConnected'));
    }

    // ── Update customer-reminder preferences — channels, timing, templates ──
    public function updatePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'email'         => ['nullable', 'boolean'],
            'whatsapp'      => ['nullable', 'boolean'],
            'days'          => ['required', 'integer', 'min:1', 'max:90'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body'    => ['nullable', 'string', 'max:5000'],
            'whatsapp_body' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant   = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];

        $settings['preferences']['subscription_reminder_email']         = $request->boolean('email');
        $settings['preferences']['subscription_reminder_whatsapp']      = $request->boolean('whatsapp');
        $settings['preferences']['subscription_reminder_days']          = (int) $request->days;
        $settings['preferences']['subscription_reminder_email_subject'] = $request->email_subject;
        $settings['preferences']['subscription_reminder_email_body']    = $request->email_body;
        $settings['preferences']['subscription_reminder_whatsapp_body'] = $request->whatsapp_body;

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Customer reminder preferences updated.');
    }

    // ── Send Reminder now — manual, on-demand version of the daily
    // automatic reminder. Bypasses the on/off toggle (an explicit staff
    // click should never be silently skipped), but still uses the tenant's
    // custom message template and only attempts channels that are
    // actually usable (contact has an email / WhatsApp is connected). ──
    public function sendReminder(int|string $id): RedirectResponse
    {
        $subscription = $this->findSubscription($id);
        $subscription->load(['contact', 'service', 'tenant']);

        $result = SubscriptionReminderService::send($subscription, emailOverride: true, whatsappOverride: true, isManual: true);

        $parts = [];
        if ($result['email'] === true)   $parts[] = 'Email sent';
        if ($result['email'] === false)  $parts[] = 'Email failed';
        if ($result['whatsapp'] === true)  $parts[] = 'WhatsApp sent';
        if ($result['whatsapp'] === false) $parts[] = 'WhatsApp failed';

        if (empty($parts)) {
            return back()->with('error', 'Could not send — contact has no email/phone, or WhatsApp is not connected.');
        }

        return back()->with('success', 'Reminder: ' . implode(', ', $parts) . '.');
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company']);
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')
            ->get(['id', 'name', 'rate', 'billing_cycle', 'duration_value', 'duration_unit']);

        return view('tenant.subscriptions.create', compact('contacts', 'services'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_id'     => ['required', 'integer', 'exists:contacts,id'],
            'service_id'     => ['required', 'integer', 'exists:services,id'],
            'invoice_id'     => ['nullable', 'integer', 'exists:invoices,id'],
            'starts_at'      => ['required', 'date'],
            'expires_at'     => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_value' => ['nullable', 'integer', 'min:1'],
            'duration_unit'  => ['nullable', 'in:days,months'],
            'notes'          => ['nullable', 'string'],
        ]);

        $data['tenant_id'] = $this->tenantId();
        $data['status']    = 'active';

        ServiceSubscription::create($data);

        return redirect()->route('tenant.subscriptions.index')
            ->with('success', 'Subscription added successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $subscription = $this->findSubscription($id);
        $contacts     = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company']);
        $services     = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')
            ->get(['id', 'name', 'rate', 'billing_cycle', 'duration_value', 'duration_unit']);

        return view('tenant.subscriptions.edit', compact('subscription', 'contacts', 'services'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $subscription = $this->findSubscription($id);

        $data = $request->validate([
            'contact_id'     => ['required', 'integer', 'exists:contacts,id'],
            'service_id'     => ['required', 'integer', 'exists:services,id'],
            'invoice_id'     => ['nullable', 'integer', 'exists:invoices,id'],
            'starts_at'      => ['required', 'date'],
            'expires_at'     => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_value' => ['nullable', 'integer', 'min:1'],
            'duration_unit'  => ['nullable', 'in:days,months'],
            'notes'          => ['nullable', 'string'],
        ]);

        $subscription->update($data);

        return redirect()->route('tenant.subscriptions.index')
            ->with('success', 'Subscription updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $this->findSubscription($id)->delete();

        return redirect()->route('tenant.subscriptions.index')
            ->with('success', 'Subscription deleted.');
    }

    // ── Cancel ────────────────────────────────────────────────────
    public function cancel(int|string $id): RedirectResponse
    {
        $subscription = $this->findSubscription($id);
        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription cancelled.');
    }

    // ── Renew — extends expiry by the subscription's own snapshotted
    // duration, starting from today or the old expiry (whichever is later),
    // reactivates it, resets the reminder guard so the next cycle can
    // alert again, and auto-creates a draft renewal Invoice prefilled with
    // the subscribed service — so staff don't have to re-search/re-select
    // it manually just to bill the customer. ─────────────────────────
    public function renew(int|string $id): RedirectResponse
    {
        $subscription = $this->findSubscription($id);
        $service      = $subscription->service;

        $base = $subscription->expires_at && $subscription->expires_at->gt(now())
            ? $subscription->expires_at
            : now();

        $newExpiry = ServiceSubscription::computeExpiry(
            $base,
            $subscription->duration_value,
            $subscription->duration_unit,
            $service?->billing_cycle
        );

        $invoice = null;

        if ($service) {
            $items = [[
                'service_id'  => $service->id,
                'description' => $service->name,
                'quantity'    => 1,
                'rate'        => (float) $service->rate,
                'tax_percent' => (float) $service->tax_percent,
            ]];

            $totals = Invoice::calculateTotals($items, 0, (float) $service->tax_percent);

            $invoice = Invoice::create(array_merge($totals, [
                'tenant_id'   => $this->tenantId(),
                'contact_id'  => $subscription->contact_id,
                'number'      => Invoice::generateNumber(),
                'date'        => now()->format('Y-m-d'),
                'due_date'    => now()->addDays(7)->format('Y-m-d'),
                'items'       => $items,
                'notes'       => "Renewal invoice for {$service->name}",
                'status'      => 'draft',
                'paid_amount' => 0,
                'created_by'  => auth()->id(),
            ]));
        }

        $subscription->update([
            'status'             => 'active',
            'expires_at'         => $newExpiry ?? $subscription->expires_at,
            'expiry_notified_at' => null,
            'invoice_id'         => $invoice?->id ?? $subscription->invoice_id,
        ]);

        $expiryText = $newExpiry?->format('d M Y') ?? '—';

        if ($invoice) {
            return redirect()->route('tenant.invoices.show', $invoice->id)
                ->with('success', "Subscription renewed — new expiry: {$expiryText}. Draft invoice {$invoice->number} created, review and send it.");
        }

        return back()->with('success', "Subscription renewed. New expiry: {$expiryText}");
    }

    // ── JSON — a contact's invoices, for the optional "link invoice" dropdown ──
    public function contactInvoices(int|string $contactId)
    {
        $invoices = Invoice::where('tenant_id', $this->tenantId())
            ->where('contact_id', $contactId)
            ->latest()
            ->get(['id', 'number', 'total', 'date']);

        return response()->json($invoices);
    }

    // ── Send Test Email — the CURRENT (possibly unsaved) template text,
    // filled with sample data, to the logged-in staff member's own email.
    // Verifies real deliverability before the template goes live to
    // customers. Not tied to any subscription. ─────────────────────────
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body'    => ['nullable', 'string', 'max:5000'],
        ]);

        $user = auth()->user();
        if (!$user->email) {
            return back()->with('error', 'Your account has no email address to send the test to.');
        }

        $subjectTemplate = $request->email_subject ?: SubscriptionReminderService::DEFAULT_EMAIL_SUBJECT;
        $bodyTemplate    = $request->email_body ?: SubscriptionReminderService::DEFAULT_EMAIL_BODY;

        $sent = SubscriptionReminderService::sendTestEmail(
            $this->tenantId(),
            $user->email,
            $user->name,
            $subjectTemplate,
            $bodyTemplate
        );

        return $sent
            ? back()->with('success', "Test email sent to {$user->email}. Check your inbox.")
            : back()->with('error', 'Could not send test email — check your Email settings.');
    }

    // ── Reminder History — every Email/WhatsApp reminder attempt, manual
    // or automatic, newest first. ────────────────────────────────────
    public function history(Request $request): View
    {
        $query = \App\Models\SubscriptionReminderLog::with(['contact', 'subscription.service'])
            ->where('tenant_id', $this->tenantId())
            ->latest('sent_at');

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('tenant.subscriptions.history', compact('logs'));
    }
}
