<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Unauthenticated, tenant-agnostic controller for the customer-facing
// quotation link. Access control is the unguessable public_token itself,
// not auth/tenant middleware — this is deliberately outside the tenant.* group.
class QuotationController extends Controller
{
    // Only the tenant scope is dropped (no auth'd user to scope by) — the
    // soft-delete scope stays in place so a deleted quotation's old public
    // link correctly 404s instead of remaining accessible.
    private function findByToken(string $token): Quotation
    {
        return Quotation::withoutGlobalScope('tenant')
            ->where('public_token', $token)
            ->firstOrFail();
    }

    public function show(string $token): View
    {
        $quotation = $this->findByToken($token);
        $quotation->load(['contact', 'tenant']);

        return view('public.quotation-show', compact('quotation'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $quotation = $this->findByToken($token);

        if ($quotation->hasCustomerResponded()) {
            return back()->with('info', 'This quotation has already been responded to.');
        }

        if ($quotation->isExpired()) {
            return back()->with('error', 'This quotation has expired and can no longer be accepted.');
        }

        $data = $request->validate([
            'signed_name'    => ['required', 'string', 'max:150'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $quotation->update([
            'status'                 => 'accepted',
            'signed_name'            => $data['signed_name'],
            'signature_data'         => $data['signature_data'] ?? null,
            'customer_response_ip'   => $request->ip(),
            'customer_responded_at'  => now(),
        ]);

        QuotationService::accept($quotation);

        return back()->with('success', 'Thank you! The quotation has been accepted.');
    }

    public function reject(Request $request, string $token): RedirectResponse
    {
        $quotation = $this->findByToken($token);

        if ($quotation->hasCustomerResponded()) {
            return back()->with('info', 'This quotation has already been responded to.');
        }

        if ($quotation->isExpired()) {
            return back()->with('error', 'This quotation has expired.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $quotation->update([
            'status'                => 'rejected',
            'rejected_reason'       => $data['reason'] ?? null,
            'customer_response_ip'  => $request->ip(),
            'customer_responded_at' => now(),
        ]);

        return back()->with('info', 'The quotation has been marked as rejected.');
    }
}
