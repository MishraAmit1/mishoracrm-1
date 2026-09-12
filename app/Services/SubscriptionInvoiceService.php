<?php

namespace App\Services;

use App\Helpers\NumberToWords;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// One place that owns the platform-billing tax invoice: numbering, the
// view-model every renderer shares, the PDF, and email/WhatsApp delivery.
// A "subscription" row IS the invoice (one paid term = one invoice).
class SubscriptionInvoiceService
{
    // GST SAC for "on-line software / SaaS" — Licensing services for the
    // right to use IPR. Overridable in the superadmin billing profile.
    public const DEFAULT_SAC = '997331';

    public const DEFAULT_TERMS = "This is a computer-generated tax invoice and does not require a physical signature.\n"
        . 'Subscription fees are non-refundable except as required by law. For billing queries, contact our support team.';

    // ── Numbering ─────────────────────────────────────────────────

    // Issue an invoice number for a paid subscription. Idempotent — returns
    // the same subscription untouched if it already has one. FY-scoped
    // running sequence, e.g. "MC/26-27/00042".
    public function issue(Subscription $subscription): Subscription
    {
        if ($subscription->hasInvoice()) {
            return $subscription;
        }

        if (!$subscription->isInvoiceable()) {
            throw new \RuntimeException('Subscription #' . $subscription->id . ' is not invoiceable (no payment on record).');
        }

        $issuedAt = $subscription->started_at ?? $subscription->created_at ?? now();

        DB::transaction(function () use ($subscription, $issuedAt) {
            $prefix = $this->setting('invoice_prefix', $this->defaultPrefix());
            $fy     = $this->financialYearLabel($issuedAt);
            $like   = $prefix . '/' . $fy . '/%';

            $lastNumber = Subscription::query()
                ->where('invoice_number', 'like', $like)
                ->orderByDesc('invoice_issued_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('invoice_number');

            $nextSeq = 1;
            if ($lastNumber && preg_match('/(\d+)$/', $lastNumber, $m)) {
                $nextSeq = (int) $m[1] + 1;
            }

            $subscription->forceFill([
                'invoice_number'    => sprintf('%s/%s/%05d', $prefix, $fy, $nextSeq),
                'invoice_issued_at' => $subscription->invoice_issued_at ?? $issuedAt,
            ])->save();
        });

        return $subscription->refresh();
    }

    // ── View-model shared by the PDF + email ──────────────────────

    public function invoiceData(Subscription $subscription): array
    {
        $subscription->loadMissing(['plan', 'tenant', 'coupon']);
        $tenant = $subscription->tenant;

        $amounts   = $this->resolveAmounts($subscription);
        $seller    = $this->profile();
        $buyerState = $tenant?->companyState();

        $gst = GstService::breakdown(
            $amounts['gst_amount'],
            $seller['state'],
            $buyerState,
        );

        $issuedAt = $subscription->invoice_issued_at
            ?? $subscription->started_at
            ?? $subscription->created_at
            ?? now();

        return [
            'invoice_number' => $subscription->invoice_number,
            'issued_at'      => $issuedAt,
            'seller'         => $seller,
            'buyer'          => [
                'name'    => $tenant?->name ?? '—',
                'email'   => $tenant?->email,
                'phone'   => $tenant?->phone,
                'address' => $tenant?->settings['address'] ?? null,
                'city'    => $tenant?->settings['city'] ?? null,
                'state'   => GstService::stateName($buyerState) ?? ($tenant?->settings['state'] ?? null),
                'pincode' => $tenant?->settings['pincode'] ?? null,
                'gstin'   => $tenant?->gstin(),
            ],
            'line' => [
                'title'       => ($subscription->plan?->name ?? 'Subscription') . ' Plan',
                'description' => ucfirst($subscription->billing_cycle) . ' subscription — '
                    . optional($subscription->started_at)->format('d M Y') . ' to '
                    . optional($subscription->ends_at)->format('d M Y'),
                'sac'         => $this->setting('invoice_hsn_sac', self::DEFAULT_SAC),
                'qty'         => 1,
            ],
            'billing_cycle'   => $subscription->billing_cycle,
            'period_start'    => $subscription->started_at,
            'period_end'      => $subscription->ends_at,
            'currency'        => '₹',
            'original_amount' => $amounts['original_amount'],
            'discount_amount' => $amounts['discount_amount'],
            'coupon_code'     => $subscription->coupon?->code,
            'taxable_amount'  => $amounts['taxable_amount'],
            'gst_percentage'  => $amounts['gst_percentage'],
            'gst_amount'      => $amounts['gst_amount'],
            'cgst_amount'     => $gst['cgst_amount'],
            'sgst_amount'     => $gst['sgst_amount'],
            'igst_amount'     => $gst['igst_amount'],
            'is_inter_state'  => $gst['is_inter_state'],
            'place_of_supply' => GstService::stateName($buyerState)
                ?? GstService::stateName($seller['state'])
                ?? '—',
            'total_amount'    => $amounts['total_amount'],
            'amount_in_words' => NumberToWords::convert($amounts['total_amount']) . ' Only',
            'payment' => [
                'method'     => $subscription->razorpay_payment_id ? 'Razorpay (Online)' : 'Recorded by Mishora CRM',
                'reference'  => $subscription->razorpay_payment_id,
                'order_id'   => $subscription->razorpay_order_id,
                'paid_at'    => $subscription->started_at ?? $issuedAt,
            ],
            'terms'       => $this->setting('invoice_terms', self::DEFAULT_TERMS),
            'footer_note' => $this->setting('invoice_footer_note', null),
            'primary_color' => $this->setting('invoice_primary_color', '#1e293b'),
            'accent_color'  => $this->setting('invoice_accent_color', '#4f46e5'),
        ];
    }

    // Stored GST columns are authoritative once present; older rows (added
    // before the GST columns existed, 2026-09-05) are reconstructed from
    // the plan price so their invoices still add up.
    private function resolveAmounts(Subscription $subscription): array
    {
        $storedTotal = (float) $subscription->total_amount;
        $storedGst   = (float) $subscription->gst_amount;
        $gstPct      = (float) $subscription->gst_percentage;
        $discount    = (float) $subscription->discount_amount;

        if ($storedTotal > 0) {
            $taxable  = round($storedTotal - $storedGst, 2);
            $original = (float) $subscription->original_amount ?: round($taxable + $discount, 2);

            return [
                'original_amount' => round($original, 2),
                'discount_amount' => round($discount, 2),
                'taxable_amount'  => $taxable,
                'gst_percentage'  => $gstPct ?: ($taxable > 0 ? round($storedGst / $taxable * 100) : 0),
                'gst_amount'      => round($storedGst, 2),
                'total_amount'    => round($storedTotal, 2),
            ];
        }

        // ── Legacy / pre-GST reconstruction ──
        $plan     = $subscription->plan;
        $original = (float) $subscription->original_amount;
        if ($original <= 0 && $plan) {
            $original = (float) ($subscription->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price);
        }
        $taxable = round(max(0, $original - $discount), 2);

        return [
            'original_amount' => round($original, 2),
            'discount_amount' => round($discount, 2),
            'taxable_amount'  => $taxable,
            'gst_percentage'  => 0.0,
            'gst_amount'      => 0.0,
            'total_amount'    => $taxable,
        ];
    }

    // ── PDF ───────────────────────────────────────────────────────

    public function pdf(Subscription $subscription)
    {
        return Pdf::loadView('billing.subscription-invoice-pdf', [
            'inv' => $this->invoiceData($subscription),
        ])->setPaper('a4', 'portrait');
    }

    public function filename(Subscription $subscription): string
    {
        $safe = str_replace(['/', '\\', ' '], '-', (string) $subscription->invoice_number);

        return 'Tax-Invoice-' . ($safe ?: $subscription->id) . '.pdf';
    }

    // ── Delivery ──────────────────────────────────────────────────

    // Issue (if needed) then email + WhatsApp the invoice to the tenant's
    // admins. Every leg is best-effort — a delivery failure must never
    // bubble up into the payment flow.
    public function issueAndDeliver(Subscription $subscription): array
    {
        try {
            $this->issue($subscription);
        } catch (\Throwable $e) {
            Log::error('Subscription invoice issue failed: ' . $e->getMessage(), ['subscription_id' => $subscription->id]);
            return ['email' => false, 'whatsapp' => false];
        }

        return $this->deliver($subscription);
    }

    public function deliver(Subscription $subscription): array
    {
        $subscription->loadMissing(['plan', 'tenant']);
        $tenant = $subscription->tenant;

        $result = ['email' => null, 'whatsapp' => null];

        if (!$tenant || !$subscription->hasInvoice()) {
            return $result;
        }

        $admins = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_type', 'tenant_admin')
            ->where('is_active', true)
            ->get();

        $emailTo = $admins->pluck('email')->filter()->unique()->values();
        if ($emailTo->isEmpty() && $tenant->email) {
            $emailTo = collect([$tenant->email]);
        }

        $delivery = $subscription->invoice_delivery ?? [];

        // ── Email (system mailer — platform billing, not tenant SMTP) ──
        if ($emailTo->isNotEmpty()) {
            try {
                $pdf = $this->pdf($subscription)->output();
                Mail::to($emailTo->first())
                    ->cc($emailTo->slice(1)->all())
                    ->send(new SubscriptionInvoiceMail($this->invoiceData($subscription), $pdf, $this->filename($subscription)));

                $delivery['email'] = ['status' => 'sent', 'to' => $emailTo->all(), 'at' => now()->toIso8601String()];
                $result['email']   = true;
            } catch (\Throwable $e) {
                Log::error('Subscription invoice email failed: ' . $e->getMessage(), ['subscription_id' => $subscription->id]);
                $delivery['email'] = ['status' => 'failed', 'to' => $emailTo->all(), 'at' => now()->toIso8601String(), 'error' => $e->getMessage()];
                $result['email']   = false;
            }
        }

        // ── WhatsApp (platform Cloud API — skipped silently if unconfigured) ──
        $waTo = $admins->pluck('phone')->filter()->first() ?: $tenant->phone;
        if ($waTo && PlatformWhatsappService::enabled()) {
            $message = $this->whatsappMessage($subscription);
            $res     = PlatformWhatsappService::sendText($waTo, $message);

            $delivery['whatsapp'] = [
                'status' => $res['ok'] ? 'sent' : 'failed',
                'to'     => $waTo,
                'at'     => now()->toIso8601String(),
            ] + ($res['error'] ? ['error' => $res['error']] : []);
            $result['whatsapp'] = $res['ok'];
        }

        $subscription->forceFill(['invoice_delivery' => $delivery])->save();

        return $result;
    }

    public function whatsappMessage(Subscription $subscription): string
    {
        $data = $this->invoiceData($subscription);
        $name = $data['seller']['name'];

        return "*{$name}* — Tax Invoice {$data['invoice_number']}\n\n"
            . "Plan: {$data['line']['title']} ({$data['billing_cycle']})\n"
            . "Amount paid: {$data['currency']}" . number_format($data['total_amount'], 2) . "\n"
            . 'Valid till: ' . optional($subscription->ends_at)->format('d M Y') . "\n\n"
            . "Download your invoice:\n" . route('tenant.subscription.invoice', $subscription);
    }

    // wa.me deep link — always-available manual fallback shown in the UI
    // when the platform WhatsApp API isn't configured.
    public function whatsappShareLink(Subscription $subscription): ?string
    {
        $subscription->loadMissing('tenant');
        $phone = preg_replace('/\D/', '', (string) $subscription->tenant?->phone);
        if (!$phone) {
            return null;
        }
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($this->whatsappMessage($subscription));
    }

    // ── Billing profile (seller) ─────────────────────────────────

    public function profile(): array
    {
        return [
            'name'    => $this->setting('billing_legal_name', config('app.name', 'Mishora CRM')),
            'address' => $this->setting('billing_address', null),
            'city'    => $this->setting('billing_city', null),
            'state'   => $this->setting('billing_state', null),
            'pincode' => $this->setting('billing_pincode', null),
            'gstin'   => $this->setting('billing_gstin', null),
            'pan'     => $this->setting('billing_pan', null),
            'email'   => $this->setting('billing_email', config('mail.from.address')),
            'phone'   => $this->setting('billing_phone', null),
            'website' => $this->setting('billing_website', null),
            'logo'    => $this->logoPath(),
        ];
    }

    private function logoPath(): ?string
    {
        foreach (['logo/logo.png', 'logo/logo.jpeg', 'logo/logo.jpg', 'logo.png'] as $rel) {
            $abs = public_path($rel);
            if (is_file($abs)) {
                return $abs;
            }
        }

        return null;
    }

    private function defaultPrefix(): string
    {
        $name    = config('app.name', 'INV');
        $initials = collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->map(fn ($w) => strtoupper($w[0]))
            ->take(3)
            ->implode('');

        return $initials ?: 'INV';
    }

    // "26-27" for any date in FY 2026-27 (Indian FY starts 1 April).
    private function financialYearLabel(Carbon|\DateTimeInterface $date): string
    {
        $date  = $date instanceof Carbon ? $date : Carbon::instance($date);
        $start = $date->month >= 4 ? $date->year : $date->year - 1;

        return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
    }

    private function setting(string $key, $default)
    {
        $value = PlatformSetting::get($key);

        return ($value === null || $value === '') ? $default : $value;
    }
}
