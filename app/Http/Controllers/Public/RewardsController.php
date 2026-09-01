<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LoyaltyOtp;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use App\Services\EmailService;
use App\Services\LoyaltyService;
use App\Services\WhatsappChatbotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Unauthenticated "check my rewards" page. A visitor proves ownership of a
// phone/email with a one-time code before any points data is shown
// (see docs/customer-loyalty.txt §4). Token-guarded per tenant, opt-in.
class RewardsController extends Controller
{
    private function findTenant(string $token): Tenant
    {
        $tenant = Tenant::whereJsonContains('settings->rewards_token', $token)->firstOrFail();

        abort_unless($tenant->loyaltyPublicLookupEnabled(), 404);

        return $tenant;
    }

    private function sessionKey(Tenant $tenant): string
    {
        return "rewards_verified_contact_{$tenant->id}";
    }

    // ── Landing — enter identifier, or show the verified result ──────
    public function show(Request $request, string $token): View
    {
        $tenant = $this->findTenant($token);

        $verifiedId = $request->session()->get($this->sessionKey($tenant));
        if ($verifiedId) {
            $contact = Contact::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)->find($verifiedId);
            if ($contact) {
                return view('public.rewards-result', [
                    'tenant'   => $tenant,
                    'token'    => $token,
                    'snapshot' => app(LoyaltyService::class)->snapshot($contact),
                ]);
            }
        }

        return view('public.rewards-show', [
            'tenant' => $tenant,
            'token'  => $token,
            'stage'  => 'identify',
            'otpId'  => null,
            'notice' => null,
        ]);
    }

    // ── Step 1 — send the code ──────────────────────────────────────
    public function requestOtp(Request $request, string $token): RedirectResponse|View
    {
        $tenant = $this->findTenant($token);

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
        ]);

        $identifier = trim($data['identifier']);

        // Throttle repeat requests for the same identifier.
        $recent = LoyaltyOtp::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('identifier', $identifier)
            ->where('created_at', '>=', now()->subSeconds(LoyaltyOtp::RESEND_WINDOW))
            ->exists();

        if ($recent) {
            return back()->with('error', 'A code was just sent. Please wait a minute before trying again.');
        }

        $contact = $this->matchContact($tenant, $identifier);

        // Only actually send when we found someone AND have a channel, but the
        // response is identical either way so account existence never leaks.
        $otpId = null;
        if ($contact) {
            $channel = $this->channelFor($contact, $identifier);
            if ($channel) {
                [$otp, $code] = LoyaltyOtp::issue($tenant->id, $contact, $identifier, $channel, $request->ip());
                $this->deliver($tenant, $contact, $channel, $code);
                $otpId = $otp->id;
            }
        }

        // Always render the code-entry step. A missing/blank otpId just means
        // "no code is coming" — the verify step will reject anything entered.
        return view('public.rewards-show', [
            'tenant'     => $tenant,
            'token'      => $token,
            'stage'      => 'verify',
            'otpId'      => $otpId,
            'identifier' => $identifier,
            'notice'     => 'If an account matches, a 6-digit code is on its way.',
        ]);
    }

    // ── Step 2 — verify the code ────────────────────────────────────
    public function verify(Request $request, string $token): RedirectResponse|View
    {
        $tenant = $this->findTenant($token);

        $data = $request->validate([
            'otp_id' => ['required', 'integer'],
            'code'   => ['required', 'string', 'max:6'],
        ]);

        $otp = LoyaltyOtp::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->find($data['otp_id']);

        if (!$otp || !$otp->attempt(trim($data['code']))) {
            return back()
                ->withInput()
                ->with('error', 'That code is incorrect or has expired. Please start again.');
        }

        $request->session()->put($this->sessionKey($tenant), $otp->contact_id);

        return redirect()->route('public.rewards.show', $token);
    }

    public function logout(Request $request, string $token): RedirectResponse
    {
        $tenant = $this->findTenant($token);
        $request->session()->forget($this->sessionKey($tenant));

        return redirect()->route('public.rewards.show', $token);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function matchContact(Tenant $tenant, string $identifier): ?Contact
    {
        $q = Contact::withoutGlobalScopes()->where('tenant_id', $tenant->id);

        if (str_contains($identifier, '@')) {
            return $q->where('email', $identifier)->first();
        }

        $digits = preg_replace('/\D/', '', $identifier);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        return $q->whereRaw(
            "RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?",
            [$last10]
        )->first();
    }

    private function channelFor(Contact $contact, string $identifier): ?string
    {
        $isEmail = str_contains($identifier, '@');

        if ($isEmail && $contact->primaryEmail()) {
            return 'email';
        }

        if (!$isEmail && $contact->phone) {
            $wa = WhatsappSetting::forTenant($contact->tenant_id);
            if ($wa->exists && $wa->is_connected) {
                return 'whatsapp';
            }
        }

        // Fall back to whatever we can reach them on.
        if ($contact->primaryEmail()) {
            return 'email';
        }

        return null;
    }

    private function deliver(Tenant $tenant, Contact $contact, string $channel, string $code): void
    {
        $text = "Your {$tenant->name} rewards code is {$code}. It expires in " . LoyaltyOtp::TTL_MINUTES . ' minutes.';

        try {
            if ($channel === 'whatsapp') {
                $waId = preg_replace('/\D/', '', $contact->phone);
                WhatsappChatbotService::forTenant($tenant->id)->sendMessage($waId, $text);
            } else {
                EmailService::send($tenant->id, $contact->primaryEmail(), $contact->name, "{$tenant->name} rewards code", '<p>' . e($text) . '</p>');
            }
        } catch (\Throwable $e) {
            // Delivery failures are silent — the visitor just won't get a code.
        }
    }
}
