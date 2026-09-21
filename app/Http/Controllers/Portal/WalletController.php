<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Tenant;
use App\Services\CustomerLinkService;
use App\Services\LoyaltyCampaignService;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

// The customer's cross-shop wallet. No tenant user is authenticated here, so
// every Contact is fetched with an explicit customer_id + tenant_id — never
// through ambient tenant scoping (docs/customer-portal-loyalty.txt §4, §12).
class WalletController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    // One shop's verified Contact for this customer, or null. Both modules
    // must be ON for the shop to exist as far as the wallet is concerned.
    private function verifiedContact(Tenant $tenant): ?Contact
    {
        if (!$tenant->hasModuleEnabled('loyalty') || !$tenant->hasModuleEnabled('customer_portal')) {
            return null;
        }

        return Contact::withoutGlobalScopes()
            ->with('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $this->customer()->id)
            ->where('phone_verified', true)
            ->orderByDesc('loyalty_lifetime_points')
            ->orderBy('id')
            ->first();
    }

    // ── Card list ────────────────────────────────────────────────
    public function index(LoyaltyService $loyalty, LoyaltyCampaignService $campaigns): View
    {
        $customer = $this->customer();

        $cards = $customer->walletContacts()->map(fn (Contact $contact) => [
            'tenant'   => $contact->tenant,
            'snapshot' => $loyalty->snapshot($contact),
            'offers'   => $campaigns->activeOffersFor($contact)->count(),
        ]);

        return view('portal.wallet.index', [
            'customer' => $customer,
            'cards'    => $cards,
            'pending'  => $customer->pendingContacts(),
        ]);
    }

    // ── One shop ─────────────────────────────────────────────────
    public function show(Tenant $tenant, LoyaltyService $loyalty, LoyaltyCampaignService $campaigns): View
    {
        $contact = $this->verifiedContact($tenant);
        abort_unless($contact, 404);

        return view('portal.wallet.show', [
            'tenant'   => $tenant,
            'snapshot' => $loyalty->snapshot($contact),
            'offers'   => $campaigns->activeOffersFor($contact),
        ]);
    }

    // ── Short-lived "show at counter" QR payload ─────────────────
    // A 90-second signed RELATIVE url carrying only the customer id — never the
    // phone number — that the shop's staff scan. The staff endpoint resolves it
    // to that shop's own contact and accepts each signature once.
    public function qr(Tenant $tenant): JsonResponse
    {
        abort_unless($this->verifiedContact($tenant), 404);

        $ttl = 90;

        return response()->json([
            'payload'    => URL::temporarySignedRoute(
                'tenant.loyalty.counter.scan-qr',
                now()->addSeconds($ttl),
                ['customer' => $this->customer()->id],
                absolute: false,
            ),
            'expires_in' => $ttl,
        ])->header('Cache-Control', 'no-store');
    }

    // ── "Is this you?" answer for a linked-but-unconfirmed contact ──
    public function confirm(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($tenant->hasModuleEnabled('loyalty') && $tenant->hasModuleEnabled('customer_portal'), 404);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'answer'     => ['required', 'in:yes,no'],
        ]);

        $customer = $this->customer();

        $contact = Contact::withoutGlobalScopes()
            ->where('id', $data['contact_id'])
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->where('phone_verified', false)
            ->first();
        abort_unless($contact, 404);

        app(CustomerLinkService::class)->confirmContact($customer, $contact, $data['answer'] === 'yes');

        return redirect()->route('portal.wallet.index')->with(
            'success',
            $data['answer'] === 'yes'
                ? "Great — {$tenant->name} has been added to your wallet."
                : "Thanks — we've let {$tenant->name} know that record isn't yours."
        );
    }
}
