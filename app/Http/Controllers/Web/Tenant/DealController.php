<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\QuotationService;
use App\Services\WebhookService;
use Carbon\Carbon;
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
        $tenantId  = auth()->user()->tenant_id;
        $staffList = $this->getStaffList();
        $contacts  = Contact::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
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

        $data['stage_changed_at'] = now();

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
            'quotations',
        ]);

        $staffList = $this->getStaffList();
        $stages    = Deal::stages();

        return view('tenant.deals.show', compact('deal', 'staffList', 'stages'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $tenantId  = auth()->user()->tenant_id;
        $deal      = $this->findDeal($id);
        $staffList = $this->getStaffList();
        $contacts  = Contact::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
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
        $justWon = $data['stage'] === 'won' && $deal->stage !== 'won';
        if ($justWon) {
            $data['actual_close_date'] = now()->toDateString();
        }

        // Auto set probability
        if (empty($data['probability'])) {
            $data['probability'] = $this->defaultProbability($data['stage']);
        }

        // Track stage change time
        if (isset($data['stage']) && $data['stage'] !== $deal->stage) {
            $data['stage_changed_at'] = now();
        }

        $deal->update($data);

        if ($justWon) {
            $this->syncQuotationAccepted($deal);
        }

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

        $data['stage_changed_at'] = now();

        $deal->update($data);

        if ($request->stage === 'won') {
            $this->syncQuotationAccepted($deal);
        }

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
            'stage_changed_at'  => now(),
        ]);

        WebhookService::fire('deal.won', $deal->tenant_id, [
            'id'           => $deal->id,
            'title'        => $deal->title,
            'value'        => $deal->value,
            'contact_name' => $deal->contact?->name,
            'contact_phone'=> $deal->contact?->phone,
            'close_date'   => $deal->actual_close_date,
        ]);

        $this->syncQuotationAccepted($deal);

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
            'stage_changed_at'  => now(),
        ]);

        WebhookService::fire('deal.lost', $deal->tenant_id, [
            'id'          => $deal->id,
            'title'       => $deal->title,
            'value'       => $deal->value,
            'lost_reason' => $deal->lost_reason,
        ]);

        return back()->with('success', 'Deal marked as lost.');
    }

    // ── Pipeline Analytics ────────────────────────────────────────
    public function pipelineAnalytics(): View
    {
        $tid = auth()->user()->tenant_id;

        // Open pipeline
        $openDeals       = Deal::where('tenant_id', $tid)->open()->get();
        $totalPipeline   = $openDeals->sum('value');
        $weightedForecast = $openDeals->sum(fn($d) => $d->value * ($d->probability / 100));

        // Win/loss this year
        $wonCount  = Deal::where('tenant_id', $tid)->won()->whereYear('actual_close_date', now()->year)->count();
        $lostCount = Deal::where('tenant_id', $tid)->lost()->whereYear('actual_close_date', now()->year)->count();
        $winRate   = ($wonCount + $lostCount) > 0
            ? round(($wonCount / ($wonCount + $lostCount)) * 100)
            : 0;

        // Avg deal size (won all-time)
        $avgDealSize = (float) (Deal::where('tenant_id', $tid)->won()->avg('value') ?? 0);

        // Avg days to close (won with actual_close_date)
        $avgDaysToClose = Deal::where('tenant_id', $tid)
            ->won()
            ->whereNotNull('actual_close_date')
            ->get()
            ->avg(fn($d) => $d->created_at->diffInDays($d->actual_close_date)) ?? 0;

        // Stage breakdown
        $stageData = Deal::where('tenant_id', $tid)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        // Monthly closed revenue + win/loss counts — last 6 months
        $monthlyRevenue = collect();
        $winLossData    = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m   = now()->subMonths($i);
            $rev = Deal::where('tenant_id', $tid)->won()
                ->whereYear('actual_close_date', $m->year)
                ->whereMonth('actual_close_date', $m->month)
                ->sum('value');
            $w = Deal::where('tenant_id', $tid)->won()
                ->whereYear('actual_close_date', $m->year)
                ->whereMonth('actual_close_date', $m->month)->count();
            $l = Deal::where('tenant_id', $tid)->lost()
                ->whereYear('actual_close_date', $m->year)
                ->whereMonth('actual_close_date', $m->month)->count();

            $monthlyRevenue->push(['month' => $m->format('M Y'), 'short' => $m->format('M'), 'value' => (float) $rev]);
            $winLossData->push(['month' => $m->format('M'), 'won' => $w, 'lost' => $l]);
        }

        // Deals closing this month
        $closingThisMonth = Deal::where('tenant_id', $tid)
            ->open()
            ->whereMonth('expected_close_date', now()->month)
            ->whereYear('expected_close_date', now()->year)
            ->with(['contact', 'assignedTo'])
            ->orderBy('expected_close_date')
            ->get();

        // Top 5 open deals by value
        $topDeals = Deal::where('tenant_id', $tid)
            ->open()
            ->with(['contact', 'assignedTo'])
            ->orderByDesc('value')
            ->limit(5)
            ->get();

        // Funnel conversion (stage counts in pipeline order, excluding won/lost)
        $pipelineStages = ['new', 'proposal', 'negotiation'];
        $funnelData     = collect($pipelineStages)->map(function ($stage) use ($stageData) {
            $row = $stageData->get($stage);
            return ['stage' => $stage, 'count' => $row->count ?? 0, 'total' => (float) ($row->total ?? 0)];
        });

        $stages = Deal::stages();

        return view('tenant.deals.pipeline-analytics', compact(
            'totalPipeline', 'weightedForecast', 'winRate', 'avgDealSize',
            'avgDaysToClose', 'stageData', 'monthlyRevenue', 'winLossData',
            'closingThisMonth', 'topDeals', 'funnelData',
            'stages', 'wonCount', 'lostCount'
        ));
    }

    // ── Deal won → auto-accept its latest pending quotation ────────
    private function syncQuotationAccepted(Deal $deal): void
    {
        $quotation = $deal->quotations()
            ->whereNotIn('status', ['accepted', 'rejected'])
            ->latest()
            ->first();

        if ($quotation) {
            QuotationService::accept($quotation);
        }
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
