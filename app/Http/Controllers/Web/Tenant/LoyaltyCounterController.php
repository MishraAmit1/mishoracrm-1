<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Invoice;
use App\Services\LoyaltyCampaignService;
use App\Services\LoyaltyService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

// The staff-side counter for shops that don't (or don't always) raise a bill:
// scan the customer's wallet QR — or type their phone — then stamp, redeem or
// apply an offer in a tap. Every Contact here is looked up inside the STAFF
// member's own tenant, so a scan can never surface another shop's customer.
class LoyaltyCounterController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    public function index(): View
    {
        $tenant = auth()->user()->tenant;

        return view('tenant.loyalty.counter', [
            'tenant' => $tenant,
            'mode'   => $tenant->loyaltySettings()['mode'] ?? 'points',
        ]);
    }

    // ── Wallet QR → this shop's card for that customer ───────────
    // Reached only with a valid, unexpired signed URL (route middleware).
    // Each signature works once, so a screenshot of the QR is worthless.
    public function scanQr(Request $request, int $customer): JsonResponse
    {
        if (!Cache::add('portal_qr_used:' . $request->query('signature'), true, 180)) {
            return response()->json(['message' => 'This QR was already used. Ask the customer to refresh their code.'], 410);
        }

        $contact = Contact::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $customer)
            ->where('phone_verified', true)
            ->orderByDesc('loyalty_lifetime_points')
            ->first();

        if (!$contact) {
            return response()->json(['message' => "This customer isn't a member here yet — look them up by phone instead."], 404);
        }

        return response()->json($this->card($contact));
    }

    // ── "Type phone" fallback ────────────────────────────────────
    public function resolvePhone(Request $request): JsonResponse
    {
        $v = $this->check($request, ['phone' => ['required', 'string', 'max:20']]);
        if ($v->fails()) {
            return $this->invalid($v);
        }
        $data = $v->validated();

        $digits = preg_replace('/\D/', '', $data['phone']);
        $phone  = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        if (strlen($phone) < 10) {
            return response()->json(['message' => 'Enter a valid 10-digit phone number.'], 422);
        }

        $contact = Contact::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('phone_normalized', $phone)
            ->orderByDesc('loyalty_lifetime_points')
            ->first();

        if (!$contact) {
            return response()->json(['message' => 'No customer with that number. Add them as a contact first.'], 404);
        }

        return response()->json($this->card($contact));
    }

    // ── +1 stamp ─────────────────────────────────────────────────
    public function stamp(Request $request): JsonResponse
    {
        $v = $this->check($request, ['contact_id' => ['required', 'integer']]);
        if ($v->fails()) {
            return $this->invalid($v);
        }
        $contact = $this->contactOrFail($v->validated()['contact_id']);

        $result = app(LoyaltyService::class)->awardStamp($contact, auth()->id());

        return response()->json([
            'ok'      => $result['ok'],
            'message' => $result['message'],
            'card'    => $this->card($contact->fresh()),
        ], $result['ok'] ? 200 : 422);
    }

    // ── Redeem points / an offer / a stamp reward against a bill ─
    // There's no open invoice at a kirana counter, so raise a minimal one on the
    // spot and settle it in the same request. That keeps a single code path:
    // the same applyRedemption / applyCoupon / award logic as a normal invoice.
    public function checkout(Request $request): JsonResponse
    {
        $v = $this->check($request, [
            'contact_id'    => ['required', 'integer'],
            'amount'        => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'use_points'    => ['nullable', 'boolean'],
            'offer_code'    => ['nullable', 'string', 'max:20'],
            'redeem_reward' => ['nullable', 'boolean'],
        ]);
        if ($v->fails()) {
            return $this->invalid($v);
        }
        $data = $v->validated();

        $usePoints = $request->boolean('use_points');
        $reward    = $request->boolean('redeem_reward');
        $code      = trim((string) ($data['offer_code'] ?? ''));
        $amount    = round((float) ($data['amount'] ?? 0), 2);

        if (!$usePoints && !$reward && $code === '') {
            return response()->json(['ok' => false, 'message' => 'Choose points, an offer or a reward to redeem.'], 422);
        }
        if (($usePoints || $code !== '') && $amount <= 0) {
            return response()->json(['ok' => false, 'message' => 'Enter the bill amount first.'], 422);
        }

        $contact = $this->contactOrFail($data['contact_id']);
        $loyalty = app(LoyaltyService::class);
        $notes   = [];

        try {
            $invoice = DB::transaction(function () use ($contact, $amount, $usePoints, $reward, $code, $loyalty, &$notes) {
                $invoice = Invoice::create([
                    'tenant_id'   => $contact->tenant_id,
                    'contact_id'  => $contact->id,
                    'number'      => Invoice::generateNumber($contact->tenant_id),
                    'date'        => now()->toDateString(),
                    'due_date'    => now()->toDateString(),
                    'items'       => [['description' => 'Counter sale', 'quantity' => 1, 'rate' => $amount]],
                    'subtotal'    => $amount,
                    'total'       => $amount,
                    'paid_amount' => 0,
                    'status'      => 'sent',
                    'created_by'  => auth()->id(),
                ]);

                // Points first: the coupon then sizes itself against what's left.
                if ($usePoints) {
                    $r = $loyalty->applyRedemption($invoice, null, auth()->id());
                    if (!$r['ok']) {
                        throw new \RuntimeException($r['message']);
                    }
                    $notes[] = $r['message'];
                }

                if ($code !== '') {
                    $r = app(LoyaltyCampaignService::class)->applyCoupon($invoice->refresh(), $code, auth()->id());
                    if (!$r['ok']) {
                        throw new \RuntimeException($r['message']);
                    }
                    $notes[] = trim($r['message'] . ' ' . ($r['note'] ?? ''));
                }

                if ($reward) {
                    $r = $loyalty->redeemStampReward($contact, $invoice->refresh(), auth()->id());
                    if (!$r['ok']) {
                        throw new \RuntimeException($r['message']);
                    }
                    $notes[] = $r['message'];
                }

                // Points / coupon are tenders; whatever's left is paid in cash.
                $invoice->refresh();
                $cash = max(0.0, round((float) $invoice->due_amount, 2));

                if ($cash > 0) {
                    $invoice->payments()->create([
                        'amount'      => $cash,
                        'method'      => 'cash',
                        'paid_at'     => now()->toDateString(),
                        'note'        => 'Counter sale',
                        'recorded_by' => auth()->id(),
                    ]);
                }

                $invoice->update(['paid_amount' => $cash, 'status' => 'paid', 'paid_at' => now()]);

                return $invoice->refresh();
            });
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        // Same post-paid side effects as any paid invoice.
        WebhookService::fire('invoice.paid', $invoice->tenant_id, [
            'id'           => $invoice->id,
            'number'       => $invoice->number,
            'total'        => $invoice->total,
            'paid_at'      => now()->toIso8601String(),
            'contact_name' => $contact->name,
        ]);
        $loyalty->awardForInvoice($invoice);
        $loyalty->awardStampForInvoice($invoice);

        $cash = max(0.0, (float) $invoice->paid_amount);
        $notes[] = $cash > 0
            ? 'Collect ₹' . number_format($cash, 2) . ' cash.'
            : 'Nothing to collect — fully covered.';

        return response()->json([
            'ok'             => true,
            'message'        => implode(' ', array_filter($notes)),
            'invoice_number' => $invoice->number,
            'card'           => $this->card($contact->fresh()),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────

    // Validated by hand: the app's global JSON exception renderer turns a thrown
    // ValidationException into a 500, and the counter's JS needs a real 422 +
    // a readable message.
    private function check(Request $request, array $rules): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), $rules);
    }

    private function invalid(\Illuminate\Validation\Validator $validator): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    private function contactOrFail(int|string $id): Contact
    {
        return Contact::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    private function card(Contact $contact): array
    {
        $snapshot = app(LoyaltyService::class)->snapshot($contact->loadMissing('tenant'));
        unset($snapshot['recent']);

        return [
            'contact' => [
                'id'     => $contact->id,
                'name'   => $contact->name,
                'phone'  => $contact->phone,
                'linked' => $contact->customer_id && $contact->phone_verified ? true : false,
            ],
            'card'   => $snapshot,
            'offers' => app(LoyaltyCampaignService::class)->activeOffersFor($contact)
                ->map(fn ($r) => [
                    'code'  => $r->code,
                    'name'  => $r->campaign->name,
                    'label' => $r->campaign->rewardLabel(),
                ])->values(),
        ];
    }
}
