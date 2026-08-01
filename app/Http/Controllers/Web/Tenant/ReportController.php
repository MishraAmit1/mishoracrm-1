<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Date range helper ─────────────────────────────────────────
    private function dateRange(Request $request): array
    {
        $preset = $request->get('range', 'this_month');

        return match($preset) {
            'today'        => [now()->startOfDay(),           now()->endOfDay()],
            'this_week'    => [now()->startOfWeek(),          now()->endOfWeek()],
            'last_week'    => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month'   => [now()->startOfMonth(),         now()->endOfMonth()],
            'last_month'   => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(),       now()->endOfQuarter()],
            'this_year'    => [now()->startOfYear(),          now()->endOfYear()],
            'last_year'    => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'custom'       => [
                Carbon::parse($request->get('from', now()->startOfMonth())),
                Carbon::parse($request->get('to',   now()->endOfMonth())),
            ],
            default        => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    // ── Overview — main report dashboard ─────────────────────────
    public function overview(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $tid = $this->tenantId();

        // ── KPI cards
        $kpis = [
            'leads'     => Lead::whereBetween('created_at', [$from, $to])->count(),
            'deals'     => Deal::whereBetween('created_at', [$from, $to])->count(),
            'revenue'   => Invoice::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('total'),
            'contacts'  => Contact::whereBetween('created_at', [$from, $to])->count(),
            'tasks_done'=> Task::where('tenant_id', $tid)->where('status', 'completed')->whereBetween('updated_at', [$from, $to])->count(),
            'quotations'=> Quotation::whereBetween('created_at', [$from, $to])->count(),
        ];

        // ── Lead sources breakdown
        $leadSources = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // ── Deal stages breakdown
        $dealStages = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get();

        // ── Monthly revenue — last 12 months
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Lead conversion rate
        $totalLeads     = Lead::whereBetween('created_at', [$from, $to])->count();
        $convertedLeads = Lead::where('status', 'converted')->whereBetween('created_at', [$from, $to])->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        // ── Top staff by leads
        $topStaff = User::withoutGlobalScopes()
            ->where('tenant_id', $tid)
            ->withCount(['createdLeads as leads_count' => fn($q) => $q->whereBetween('created_at', [$from, $to])])
            ->orderByDesc('leads_count')
            ->limit(5)
            ->get();

        return view('tenant.reports.overview', compact(
            'kpis', 'leadSources', 'dealStages',
            'monthlyRevenue', 'conversionRate',
            'topStaff', 'from', 'to', 'request'
        ));
    }

    // ── Leads report ──────────────────────────────────────────────
    public function leads(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $statuses    = config('crm.lead.statuses');
        $sources     = config('crm.lead.sources');
        $priorities  = config('crm.lead.priorities');

        // By status
        $byStatus = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()->keyBy('status');

        // By source
        $bySource = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // By priority
        $byPriority = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get()->keyBy('priority');

        // Daily trend
        $dailyTrend = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE(created_at) as day, COUNT(*) as count")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Assigned to staff
        $byStaff = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('assigned_to, COUNT(*) as count')
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Recent leads
        $recentLeads = Lead::with(['assignedTo:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()->limit(10)->get();

        // Total + conversion
        $total      = Lead::whereBetween('created_at', [$from, $to])->count();
        $converted  = Lead::where('status','converted')->whereBetween('created_at', [$from, $to])->count();
        $lost       = Lead::where('status','lost')->whereBetween('created_at', [$from, $to])->count();

        return view('tenant.reports.leads', compact(
            'byStatus','bySource','byPriority','dailyTrend',
            'byStaff','recentLeads','total','converted','lost',
            'statuses','sources','priorities','from','to','request'
        ));
    }

    // ── Deals report ──────────────────────────────────────────────
    public function deals(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $stages = config('crm.deal.stages');

        // By stage
        $byStage = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total, AVG(value) as avg_value')
            ->groupBy('stage')
            ->get()->keyBy('stage');

        // Won deals
        $wonDeals  = Deal::where('stage','won')->whereBetween('updated_at', [$from, $to]);
        $wonCount  = $wonDeals->count();
        $wonValue  = $wonDeals->sum('value');

        // Lost deals
        $lostCount = Deal::where('stage','lost')->whereBetween('updated_at', [$from, $to])->count();

        // Win rate
        $closedTotal = $wonCount + $lostCount;
        $winRate     = $closedTotal > 0 ? round(($wonCount / $closedTotal) * 100, 1) : 0;

        // Avg deal size
        $avgDealSize = Deal::whereBetween('created_at', [$from, $to])->avg('value') ?? 0;

        // Pipeline value
        $pipelineValue = Deal::whereNotIn('stage',['won','lost'])->sum('value');

        // Monthly deal trend
        $monthlyDeals = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count, SUM(value) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top deals
        $topDeals = Deal::with(['contact:id,name','assignedTo:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // By assigned staff
        $byStaff = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('assigned_to, COUNT(*) as count, SUM(value) as total')
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('tenant.reports.deals', compact(
            'byStage','wonCount','wonValue','lostCount','winRate',
            'avgDealSize','pipelineValue','monthlyDeals',
            'topDeals','byStaff','stages','from','to','request'
        ));
    }

    // ── Revenue report ────────────────────────────────────────────
    public function revenue(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        // Summary
        $totalRevenue   = Invoice::where('status','paid')->whereBetween('paid_at', [$from, $to])->sum('total');
        $pendingRevenue = Invoice::whereIn('status',['sent','partial'])->sum('total');
        $overdueRevenue = Invoice::where('status','overdue')->sum('total');
        $totalInvoices  = Invoice::whereBetween('created_at', [$from, $to])->count();
        $paidInvoices   = Invoice::where('status','paid')->whereBetween('paid_at', [$from, $to])->count();

        // Monthly revenue
        $monthlyRevenue = Invoice::where('status','paid')
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at,'%Y-%m') as month, SUM(total) as revenue, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Quotation to Invoice conversion
        $quotationsTotal  = Quotation::whereBetween('created_at', [$from, $to])->count();
        $quotationsAccepted = Quotation::where('status','accepted')->whereBetween('created_at', [$from, $to])->count();
        $quotationRate    = $quotationsTotal > 0 ? round(($quotationsAccepted / $quotationsTotal) * 100, 1) : 0;

        // By contact
        $byContact = Invoice::where('status','paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('contact_id, SUM(total) as revenue, COUNT(*) as count')
            ->groupBy('contact_id')
            ->with('contact:id,name,company')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Invoice status breakdown
        $invoiceStatuses = Invoice::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()->keyBy('status');

        // Recent paid invoices
        $recentPaid = Invoice::where('status','paid')
            ->with(['contact:id,name'])
            ->whereBetween('paid_at', [$from, $to])
            ->latest('paid_at')
            ->limit(10)
            ->get();

        return view('tenant.reports.revenue', compact(
            'totalRevenue','pendingRevenue','overdueRevenue',
            'totalInvoices','paidInvoices','monthlyRevenue',
            'quotationsTotal','quotationsAccepted','quotationRate',
            'byContact','invoiceStatuses','recentPaid',
            'from','to','request'
        ));
    }

    // ── Staff performance ─────────────────────────────────────────
    public function staff(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $tid = $this->tenantId();

        $staffList = User::withoutGlobalScopes()
            ->where('tenant_id', $tid)
            ->where('user_type', 'staff')
            ->get()
            ->map(function ($user) use ($from, $to, $tid) {
                $leadsCreated  = Lead::where('created_by', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $leadsAssigned = Lead::where('assigned_to', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $dealsWon      = Deal::where('assigned_to', $user->id)->where('stage','won')->whereBetween('updated_at', [$from, $to])->count();
                $dealValue     = Deal::where('assigned_to', $user->id)->where('stage','won')->whereBetween('updated_at', [$from, $to])->sum('value');
                $tasksCompleted= Task::where('tenant_id', $tid)->where('assigned_to', $user->id)->where('status','completed')->whereBetween('updated_at', [$from, $to])->count();
                $followupsDone = Followup::where('created_by', $user->id)->where('status','done')->whereBetween('updated_at', [$from, $to])->count();
                $totalLeads    = Lead::where('assigned_to', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $converted     = Lead::where('assigned_to', $user->id)->where('status','converted')->whereBetween('created_at', [$from, $to])->count();

                return [
                    'user'           => $user,
                    'leads_created'  => $leadsCreated,
                    'leads_assigned' => $leadsAssigned,
                    'deals_won'      => $dealsWon,
                    'deal_value'     => $dealValue,
                    'tasks_done'     => $tasksCompleted,
                    'followups_done' => $followupsDone,
                    'conversion_rate'=> $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
                    'score'          => ($leadsCreated * 2) + ($dealsWon * 10) + ($tasksCompleted) + ($followupsDone * 2),
                ];
            })
            ->sortByDesc('score')
            ->values();

        return view('tenant.reports.staff', compact(
            'staffList', 'from', 'to', 'request'
        ));
    }
}