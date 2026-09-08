<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\PlatformWhatsappService;
use App\Services\SubscriptionInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// The platform's own company / tax identity + invoice defaults + the
// platform WhatsApp sender — everything that goes on a subscription tax
// invoice or delivers one. All stored as flat platform_settings keys.
class BillingProfileController extends Controller
{
    private const KEYS = [
        'billing_legal_name', 'billing_address', 'billing_city', 'billing_state',
        'billing_pincode', 'billing_gstin', 'billing_pan', 'billing_email',
        'billing_phone', 'billing_website',
        'invoice_prefix', 'invoice_hsn_sac', 'invoice_terms', 'invoice_footer_note',
        'invoice_primary_color', 'invoice_accent_color',
        'platform_wa_enabled', 'platform_wa_phone_number_id', 'platform_wa_access_token',
    ];

    public function edit(): View
    {
        $settings = collect(self::KEYS)->mapWithKeys(
            fn ($key) => [$key => PlatformSetting::get($key)]
        );

        return view('superadmin.billing-profile.edit', [
            'settings'    => $settings,
            'states'      => config('crm.states', []),
            'defaultSac'  => SubscriptionInvoiceService::DEFAULT_SAC,
            'defaultTerms'=> SubscriptionInvoiceService::DEFAULT_TERMS,
            'gstPercentage' => PlatformSetting::get('gst_percentage', '18'),
            'waEnabled'   => PlatformWhatsappService::enabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'billing_legal_name' => ['nullable', 'string', 'max:150'],
            'billing_address'    => ['nullable', 'string', 'max:400'],
            'billing_city'       => ['nullable', 'string', 'max:80'],
            'billing_state'      => ['nullable', 'string', 'max:80'],
            'billing_pincode'    => ['nullable', 'string', 'max:12'],
            'billing_gstin'      => ['nullable', 'string', 'max:20'],
            'billing_pan'        => ['nullable', 'string', 'max:15'],
            'billing_email'      => ['nullable', 'email', 'max:150'],
            'billing_phone'      => ['nullable', 'string', 'max:30'],
            'billing_website'    => ['nullable', 'string', 'max:150'],
            'invoice_prefix'     => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]+$/'],
            'invoice_hsn_sac'    => ['nullable', 'string', 'max:12'],
            'invoice_terms'      => ['nullable', 'string', 'max:2000'],
            'invoice_footer_note'=> ['nullable', 'string', 'max:500'],
            'invoice_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'invoice_accent_color'  => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'platform_wa_enabled'         => ['nullable', 'boolean'],
            'platform_wa_phone_number_id' => ['nullable', 'string', 'max:60'],
            'platform_wa_access_token'    => ['nullable', 'string', 'max:400'],
        ]);

        foreach (self::KEYS as $key) {
            if ($key === 'platform_wa_enabled') {
                PlatformSetting::set($key, $request->boolean('platform_wa_enabled') ? '1' : '0');
                continue;
            }

            // Keep the saved WhatsApp token if the field is left blank (it's
            // shown masked, so an empty submit means "don't change").
            if ($key === 'platform_wa_access_token' && blank($data[$key] ?? null)) {
                continue;
            }

            PlatformSetting::set($key, $data[$key] ?? null);
        }

        return back()->with('success', 'Billing profile saved — applied to every invoice from now on.');
    }
}
