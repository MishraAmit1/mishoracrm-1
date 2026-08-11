<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with(['assignedTo', 'followups'])
            ->withCount(['tasks', 'followups']);

        $query = ViewScope::apply($query, 'leads', $request->user());

        // Filters
        if ($request->filled('search'))      $query->search($request->search);
        if ($request->filled('status'))      $query->status($request->status);
        if ($request->filled('source'))      $query->source($request->source);
        if ($request->filled('priority'))    $query->priority($request->priority);
        if ($request->filled('assigned_to')) $query->assignedTo($request->assigned_to);
        if ($request->filled('date_from'))   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('created_at', '<=', $request->date_to);

        // Sort
        $sortBy  = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowed = ['name', 'created_at', 'status', 'priority', 'lead_value', 'source'];
        if (in_array($sortBy, $allowed)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min($request->get('per_page', 15), 100);
        $leads   = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => LeadResource::collection($leads->items()),
            'meta'    => [
                'current_page' => $leads->currentPage(),
                'last_page'    => $leads->lastPage(),
                'per_page'     => $leads->perPage(),
                'total'        => $leads->total(),
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(LeadRequest $request): JsonResponse
    {
        $lead = Lead::create($request->validated());
        $lead->load('assignedTo', 'createdBy');

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data'    => new LeadResource($lead),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load([
            'assignedTo',
            'createdBy',
            'followups.assignedTo',
            'tasks.assignedTo',
        ])->loadCount(['tasks', 'followups']);

        return response()->json([
            'success' => true,
            'data'    => new LeadResource($lead),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(LeadRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('modify', $lead);

        if ($request->status === 'contacted' && $lead->status !== 'contacted') {
            $lead->contacted_at = now();
        }

        if ($request->status === 'converted' && $lead->status !== 'converted') {
            $lead->converted_at = now();
        }

        $lead->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'data'    => new LeadResource($lead->fresh('assignedTo')),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('modify', $lead);
        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }

    // ── Assign ────────────────────────────────────────────────────
    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('modify', $lead);

        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $lead->update(['assigned_to' => $request->assigned_to]);

        return response()->json([
            'success' => true,
            'message' => 'Lead assigned successfully.',
            'data'    => new LeadResource($lead->fresh('assignedTo')),
        ]);
    }

    // ── Convert to contact ────────────────────────────────────────
    // public function convert(Lead $lead): JsonResponse
    // {
    //     if ($lead->isConverted()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Lead is already converted.',
    //         ], 422);
    //     }

    //     $contact = Contact::create([
    //         'tenant_id'   => $lead->tenant_id,
    //         'lead_id'     => $lead->id,
    //         'name'        => $lead->name,
    //         'phone'       => $lead->phone,
    //         'email'       => $lead->email,
    //         'company'     => $lead->company,
    //         'designation' => $lead->designation,
    //         'city'        => $lead->city,
    //         'state'       => $lead->state,
    //     ]);

    //     $lead->update([
    //         'status'       => 'converted',
    //         'converted_at' => now(),
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Lead converted to contact.',
    //         'data'    => [
    //             'lead'    => new LeadResource($lead->fresh()),
    //             'contact' => ['id' => $contact->id, 'name' => $contact->name],
    //         ],
    //     ]);
    // }

    // ── Update status ─────────────────────────────────────────────
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('modify', $lead);

        $request->validate([
            'status'      => ['required', 'in:new,contacted,qualified,proposal,negotiation,converted,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = ['status' => $request->status];
        if ($request->status === 'contacted') $updates['contacted_at'] = now();
        if ($request->status === 'converted') $updates['converted_at'] = now();
        if ($request->status === 'lost' && $request->filled('lost_reason')) {
            $updates['lost_reason'] = $request->lost_reason;
        }

        $lead->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'data'    => new LeadResource($lead->fresh()),
        ]);
    }

    // ── Stats summary ─────────────────────────────────────────────
    public function stats(): JsonResponse
    {
        $base = fn() => ViewScope::apply(Lead::query(), 'leads', auth()->user());

        return response()->json([
            'success' => true,
            'data'    => [
                'total'       => $base()->count(),
                'by_status'   => $base()->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
                'by_source'   => $base()->selectRaw('source, COUNT(*) as count')->groupBy('source')->pluck('count', 'source'),
                'by_priority' => $base()->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority'),
                'this_month'  => $base()->thisMonth()->count(),
                'today'       => $base()->today()->count(),
            ],
        ]);
    }
}