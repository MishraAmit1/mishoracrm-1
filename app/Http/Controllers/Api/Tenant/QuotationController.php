<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    // ── Find quotation — tenant scope ──────────────────────────────
    private function findQuotation(int $id): Quotation
    {
        $tenantId = auth()->user()?->tenant_id ?? app('tenant_id');
        return Quotation::where('id', $id)
                   ->where('tenant_id', $tenantId)
                   ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Quotation::with(['contact', 'lead', 'createdBy'])
            ->latest();

        $query = ViewScope::apply($query, 'quotations', $request->user(), 'created_by');

        if ($request->filled('status')) $query->status($request->status);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', "%{$request->search}%")
                    ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            });
        }

        $perPage    = min($request->get('per_page', 15), 100);
        $quotations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => QuotationResource::collection($quotations->items()),
            'meta'    => [
                'current_page' => $quotations->currentPage(),
                'last_page'    => $quotations->lastPage(),
                'per_page'     => $quotations->perPage(),
                'total'        => $quotations->total(),
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(QuotationRequest $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $tenantId  = auth()->user()?->tenant_id ?? app('tenant_id');
        $quotation = QuotationService::store($request->validated(), $tenantId, auth()->id());
        $quotation->load('contact', 'lead');

        return response()->json([
            'success' => true,
            'message' => 'Quotation created successfully.',
            'data'    => new QuotationResource($quotation),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        $quotation->load(['contact', 'lead', 'deal', 'createdBy', 'invoice']);

        return response()->json([
            'success' => true,
            'data'    => new QuotationResource($quotation),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(QuotationRequest $request, int $id): JsonResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('modify', $quotation);

        QuotationService::update($quotation, $request->validated());

        $invoice = $quotation->status === 'accepted' ? QuotationService::accept($quotation) : null;

        return response()->json([
            'success' => true,
            'message' => $invoice ? "Quotation updated. Invoice {$invoice->number} created automatically." : 'Quotation updated successfully.',
            'data'    => new QuotationResource($quotation->fresh(['contact', 'lead'])),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('delete', $quotation);
        $quotation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quotation deleted successfully.',
        ]);
    }

    // ── Update status ────────────────────────────────────────────
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
        ]);

        $quotation = $this->findQuotation($id);
        $this->authorize('modify', $quotation);
        $quotation->update(['status' => $request->status]);

        $invoice = $quotation->status === 'accepted' ? QuotationService::accept($quotation) : null;

        return response()->json([
            'success' => true,
            'message' => $invoice ? "Status updated. Invoice {$invoice->number} created automatically." : 'Status updated.',
            'data'    => new QuotationResource($quotation->fresh()),
        ]);
    }

    // ── Create a new draft version cloned from this quotation ──────
    public function newVersion(int $id): JsonResponse
    {
        $quotation = $this->findQuotation($id);
        $this->authorize('view', $quotation);
        $this->authorize('create', Quotation::class);

        $newVersion = QuotationService::createNewVersion($quotation, auth()->id());
        $newVersion->load('contact', 'lead');

        return response()->json([
            'success' => true,
            'message' => "Version {$newVersion->version} created from {$quotation->number}.",
            'data'    => new QuotationResource($newVersion),
        ], 201);
    }
}
