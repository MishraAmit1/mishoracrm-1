<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    // ── Find deal — tenant scope ──────────────────────────────────
    private function findDeal(int $id): Deal
    {
        $tenantId = auth()->user()?->tenant_id ?? app('tenant_id');
        return Deal::where('id', $id)
                   ->where('tenant_id', $tenantId)
                   ->firstOrFail();
    }

    private function defaultProbability(string $stage): int
    {
        return match($stage) {
            'new'         => 10,
            'proposal'    => 30,
            'negotiation' => 60,
            'won'         => 100,
            'lost'        => 0,
            default       => 10,
        };
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Deal::with(['contact', 'assignedTo'])
            ->withCount(['tasks', 'followups']);

        $query = ViewScope::apply($query, 'deals', $request->user());

        if ($request->filled('search'))      $query->search($request->search);
        if ($request->filled('stage'))       $query->stage($request->stage);
        if ($request->filled('assigned_to')) $query->assignedTo($request->assigned_to);
        if ($request->filled('value_min'))   $query->where('value', '>=', $request->value_min);
        if ($request->filled('value_max'))   $query->where('value', '<=', $request->value_max);

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');
        $allowed = ['title', 'value', 'stage', 'created_at', 'expected_close_date'];
        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min($request->get('per_page', 15), 100);
        $deals   = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => DealResource::collection($deals->items()),
            'meta'    => [
                'current_page' => $deals->currentPage(),
                'last_page'    => $deals->lastPage(),
                'per_page'     => $deals->perPage(),
                'total'        => $deals->total(),
            ],
        ]);
    }

    // ── Kanban — grouped by stage ─────────────────────────────────
    public function kanban(): JsonResponse
    {
        $deals = ViewScope::apply(Deal::with(['contact', 'assignedTo']), 'deals', auth()->user())
            ->get()
            ->groupBy('stage')
            ->map(fn($group) => DealResource::collection($group));

        $summary = ViewScope::apply(Deal::query(), 'deals', auth()->user())
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        return response()->json([
            'success' => true,
            'data'    => [
                'deals'   => $deals,
                'summary' => $summary,
                'stages'  => Deal::stages(),
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(DealRequest $request): JsonResponse
    {
        $data               = $request->validated();
        $data['tenant_id']  = auth()->user()?->tenant_id ?? app('tenant_id');
        $data['created_by'] = auth()->id();

        if (empty($data['probability'])) {
            $data['probability'] = $this->defaultProbability($data['stage']);
        }

        if ($data['stage'] === 'won' && empty($data['actual_close_date'])) {
            $data['actual_close_date'] = now()->toDateString();
        }

        $deal = Deal::create($data);
        $deal->load('contact', 'assignedTo');

        return response()->json([
            'success' => true,
            'message' => 'Deal created successfully.',
            'data'    => new DealResource($deal),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('view', $deal);
        $deal->load(['contact', 'lead', 'assignedTo', 'createdBy', 'tasks', 'followups'])
             ->loadCount(['tasks', 'followups']);

        return response()->json([
            'success' => true,
            'data'    => new DealResource($deal),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(DealRequest $request, int $id): JsonResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);
        $data = $request->validated();

        if ($data['stage'] === 'won' && $deal->stage !== 'won') {
            $data['actual_close_date'] = now()->toDateString();
        }

        if (empty($data['probability'])) {
            $data['probability'] = $this->defaultProbability($data['stage']);
        }

        $deal->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Deal updated successfully.',
            'data'    => new DealResource($deal->fresh(['contact', 'assignedTo'])),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);
        $deal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deal deleted successfully.',
        ]);
    }

    // ── Update stage ──────────────────────────────────────────────
    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stage'       => ['required', 'in:new,proposal,negotiation,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);

        $data = [
            'stage'       => $request->stage,
            'probability' => $this->defaultProbability($request->stage),
        ];

        if ($request->stage === 'won') {
            $data['actual_close_date'] = now()->toDateString();
        }

        if ($request->stage === 'lost' && $request->filled('lost_reason')) {
            $data['lost_reason'] = $request->lost_reason;
        }

        $deal->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Stage updated.',
            'data'    => new DealResource($deal->fresh()),
        ]);
    }

    // ── Mark Won ──────────────────────────────────────────────────
    public function markWon(int $id): JsonResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);
        $deal->update([
            'stage'             => 'won',
            'probability'       => 100,
            'actual_close_date' => now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal marked as Won!',
            'data'    => new DealResource($deal->fresh()),
        ]);
    }

    // ── Mark Lost ─────────────────────────────────────────────────
    public function markLost(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);
        $deal->update([
            'stage'             => 'lost',
            'probability'       => 0,
            'actual_close_date' => now()->toDateString(),
            'lost_reason'       => $request->lost_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal marked as lost.',
            'data'    => new DealResource($deal->fresh()),
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────
    public function stats(): JsonResponse
    {
        $base = fn() => ViewScope::apply(Deal::query(), 'deals', auth()->user());

        return response()->json([
            'success' => true,
            'data'    => [
                'total_deals'     => $base()->count(),
                'open_deals'      => $base()->open()->count(),
                'pipeline_value'  => $base()->open()->sum('value'),
                'won_this_month'  => $base()->won()->thisMonth()->count(),
                'won_value_month' => $base()->won()->thisMonth()->sum('value'),
                'by_stage'        => $base()->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
                                         ->groupBy('stage')
                                         ->get(),
            ],
        ]);
    }
}