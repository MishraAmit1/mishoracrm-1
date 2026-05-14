<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealController extends Controller
{
    // ── Find deal — tenant scope ──────────────────────────────────
    private function findDeal(int|string $id): Deal
    {
        return Deal::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Staff list — sirf current tenant ─────────────────────────
    private function getStaffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Deal::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['contact', 'assignedTo'])
            ->withCount(['tasks', 'followups']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('stage')) {
            $query->stage($request->stage);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('value_min')) {
            $query->where('value', '>=', $request->value_min);
        }

        if ($request->filled('value_max')) {
            $query->where('value', '<=', $request->value_max);
        }

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');

        $allowed = [
            'title',
            'value',
            'stage',
            'created_at',
            'expected_close_date'
        ];

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        // ── Kanban data ────────────────────────────
        $kanbanDeals = (clone $query)
            ->get()
            ->groupBy('stage');

        // ── List view data ─────────────────────────
        $deals = (clone $query)
            ->paginate(20)
            ->withQueryString();

       

        // ── Summary ────────────────────────────────
        $stageSummary = Deal::where('tenant_id', auth()->user()->tenant_id)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $view      = $request->get('view', 'kanban');
        $staffList = $this->getStaffList();
        $stages    = Deal::stages();
        

        return view('tenant.deals.index', compact(
            'deals',
            'kanbanDeals',
            'stageSummary',
            'staffList',
            'stages',
            'view'
        ));
    }
    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $staffList = $this->getStaffList();
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::orderBy('name')->get(['id', 'name']);
        $stages    = Deal::stages();

        // Pre-fill contact/lead if coming from their pages
        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        return view('tenant.deals.create', compact(
            'staffList',
            'contacts',
            'leads',
            'stages',
            'contact',
            'lead'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(DealRequest $request): RedirectResponse
    {
        $data               = $request->validated();
        $data['tenant_id']  = auth()->user()->tenant_id;
        $data['created_by'] = auth()->id();

        // Auto set probability based on stage
        if (empty($data['probability'])) {
            $data['probability'] = $this->defaultProbability($data['stage']);
        }

        // If won — set actual close date
        if ($data['stage'] === 'won' && empty($data['actual_close_date'])) {
            $data['actual_close_date'] = now()->toDateString();
        }

        $deal = Deal::create($data);

        return redirect()
            ->route('tenant.deals.show', $deal->id)
            ->with('success', "Deal '{$deal->title}' created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $deal = $this->findDeal($id);
        $deal->load([
            'contact',
            'lead',
            'assignedTo',
            'createdBy',
            // 'tasks.assignedTo',
            'followups.assignedTo',
        ]);

        $staffList = $this->getStaffList();
        $stages    = Deal::stages();

        return view('tenant.deals.show', compact('deal', 'staffList', 'stages'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $deal      = $this->findDeal($id);
        $staffList = $this->getStaffList();
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::orderBy('name')->get(['id', 'name']);
        $stages    = Deal::stages();

        return view('tenant.deals.edit', compact(
            'deal',
            'staffList',
            'contacts',
            'leads',
            'stages'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(DealRequest $request, int|string $id): RedirectResponse
    {
        $deal = $this->findDeal($id);
        $data = $request->validated();

        // Auto set actual_close_date when won
        if ($data['stage'] === 'won' && $deal->stage !== 'won') {
            $data['actual_close_date'] = now()->toDateString();
        }

        // Auto set probability
        if (empty($data['probability'])) {
            $data['probability'] = $this->defaultProbability($data['stage']);
        }

        $deal->update($data);

        return redirect()
            ->route('tenant.deals.show', $deal->id)
            ->with('success', 'Deal updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $deal  = $this->findDeal($id);
        $title = $deal->title;
        $deal->delete();

        return redirect()
            ->route('tenant.deals.index')
            ->with('success', "Deal '{$title}' deleted.");
    }

    // ── Update stage (Kanban drag or quick action) ────────────────
    public function updateStage(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'stage'       => ['required', 'in:new,proposal,negotiation,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);

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

        return back()->with('success', 'Deal stage updated.');
    }

    // ── Mark Won ──────────────────────────────────────────────────
    public function markWon(int|string $id): RedirectResponse
    {
        $deal = $this->findDeal($id);
        $deal->update([
            'stage'             => 'won',
            'probability'       => 100,
            'actual_close_date' => now()->toDateString(),
        ]);

        return back()->with('success', "Deal marked as Won! 🎉");
    }

    // ── Mark Lost ─────────────────────────────────────────────────
    public function markLost(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);
        $deal->update([
            'stage'             => 'lost',
            'probability'       => 0,
            'actual_close_date' => now()->toDateString(),
            'lost_reason'       => $request->lost_reason,
        ]);

        return back()->with('success', 'Deal marked as lost.');
    }

    // ── Default probability by stage ──────────────────────────────
    private function defaultProbability(string $stage): int
    {
        return match ($stage) {
            'new'         => 10,
            'proposal'    => 30,
            'negotiation' => 60,
            'won'         => 100,
            'lost'        => 0,
            default       => 10,
        };
    }
}
