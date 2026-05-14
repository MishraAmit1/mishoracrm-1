<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
    
        $userId = auth()->id();

        // ── Stats ─────────────────────────────────────────────────
        $stats = [
            // Leads
            'total_leads'        => Lead::count(),
            'new_leads_today'    => Lead::whereDate('created_at', today())->count(),
            'new_leads_count'    => Lead::where('status', 'new')->count(),
            'new_leads_month'    => Lead::thisMonth()->count(),

            // Deals
            'active_deals'       => Deal::whereNotIn('stage', ['won', 'lost'])->count(),
            'pipeline_value'     => Deal::whereNotIn('stage', ['won', 'lost'])->sum('value'),
            'won_this_month'     => Deal::where('stage', 'won')
                                        ->whereMonth('updated_at', now()->month)
                                        ->count(),
            'won_value_month'    => Deal::where('stage', 'won')
                                        ->whereMonth('updated_at', now()->month)
                                        ->sum('value'),

            // Revenue
            'revenue_this_month' => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->month)
                                           ->whereYear('paid_at', now()->year)
                                           ->sum('total'),
            'revenue_last_month' => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->subMonth()->month)
                                           ->whereYear('paid_at', now()->subMonth()->year)
                                           ->sum('total'),
            'invoices_paid'      => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->month)
                                           ->count(),
            'invoices_overdue'   => Invoice::where('status', 'overdue')->count(),

            // Tasks
            'tasks_pending'      => Task::where('status', 'pending')
                                        ->where(function ($q) use ($userId) {
                                            $q->where('assigned_to', $userId)
                                              ->orWhere('created_by', $userId);
                                        })->count(),
            'tasks_completed'    => Task::where('status', 'completed')
                                        ->whereMonth('completed_at', now()->month)
                                        ->count(),
            'tasks_overdue'      => Task::where('status', 'pending')
                                        ->whereNotNull('due_at')
                                        ->where('due_at', '<', now())
                                        ->count(),

            // Follow-ups
            // 'followups_today'    => Followup::whereDate('scheduled_at', today())
            //                                 ->where('status', 'scheduled')
            //                                 ->count(),

             'followups_today'    => 0,
        ];
        

        // ── Revenue trend (calculate % change) ───────────────────
        $stats['revenue_trend'] = $stats['revenue_last_month'] > 0
            ? round((($stats['revenue_this_month'] - $stats['revenue_last_month']) / $stats['revenue_last_month']) * 100, 1)
            : 0;

        // ── Revenue chart — last 12 months ────────────────────────
        $revenueRaw = Invoice::where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $chartData = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartData[] = round($revenueRaw[$m] ?? 0, 2);
        }

        // ── Pipeline by stage ─────────────────────────────────────
        $pipelineRaw = Deal::selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get();

        $stageColors = [
            'new'         => 'var(--accent)',
            'proposal'    => 'var(--amber)',
            'negotiation' => 'var(--purple)',
            'won'         => 'var(--green)',
            'lost'        => 'var(--red)',
        ];

        $maxCount = $pipelineRaw->max('count') ?: 1;
        $pipeline = $pipelineRaw->map(fn($s) => [
            'label'  => ucfirst($s->stage),
            'value'  => $s->count,
            'amount' => '₹' . number_format($s->total / 100000, 1) . 'L',
            'color'  => $stageColors[$s->stage] ?? 'var(--accent)',
            'pct'    => round(($s->count / $maxCount) * 100),
        ])->values()->toArray();

        // ── Recent leads ──────────────────────────────────────────
        $recentLeads = Lead::with('assignedTo')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'phone'    => $l->phone,
                'source'   => $l->source ?? '—',
                'status'   => $l->status,
                'assigned' => $l->assignedTo?->name ?? '—',
                'time'     => $l->created_at->diffForHumans(),
            ])->toArray();

        // ── Today's tasks ─────────────────────────────────────────
        $todayTasks = Task::with('assignedTo')
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhere('created_by', $userId);
            })
            ->where(function ($q) {
                $q->whereDate('due_at', today())
                  ->orWhere('status', 'pending');
            })
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'text'     => $t->title,
                'due'      => $t->due_at?->format('h:i A') ?? 'No time set',
                'done'     => $t->status === 'completed',
                'priority' => $t->priority,
            ])->toArray();

        // ── Recent activity ───────────────────────────────────────
        // Combine recent leads + deals + tasks into one feed
        $actLeads = Lead::latest()->limit(3)->get()->map(fn($l) => [
            'type' => 'lead',
            'text' => '<strong>' . e($l->createdBy?->name ?? 'Someone') . '</strong> added lead <strong>' . e($l->name) . '</strong>',
            'time' => $l->created_at,
        ]);

        $actDeals = Deal::latest()->limit(3)->get()->map(fn($d) => [
            'type' => 'deal',
            'text' => 'Deal <strong>' . e($d->title) . '</strong> is in <strong>' . ucfirst($d->stage) . '</strong> stage',
            'time' => $d->updated_at,
        ]);

        $actTasks = Task::where('status', 'completed')->latest('completed_at')->limit(2)->get()->map(fn($t) => [
            'type' => 'task',
            'text' => '<strong>' . e($t->assignedTo?->name ?? 'Someone') . '</strong> completed task <strong>' . e($t->title) . '</strong>',
            'time' => $t->completed_at ?? $t->updated_at,
        ]);

        $activities = $actLeads->merge($actDeals)->merge($actTasks)
            ->sortByDesc('time')
            ->take(7)
            ->map(fn($a) => [
                'type' => $a['type'],
                'text' => $a['text'],
                'time' => $a['time']->diffForHumans(),
            ])->values()->toArray();

        // ── Lead source breakdown ─────────────────────────────────
        $leadSources = Lead::selectRaw('source, COUNT(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->mapWithKeys(fn($s) => [$s->source => $s->count])
            ->toArray();

        // ── New leads badge for sidebar ───────────────────────────
        $newLeadsCount = $stats['new_leads_count'];

        // ── Compile all data ──────────────────────────────────────
        $data = compact(
            'stats',
            'chartData',
            'pipeline',
            'recentLeads',
            'todayTasks',
            'activities',
            'leadSources',
            'newLeadsCount'
        );

        // ── Web or API ────────────────────────────────────────────
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        // return response()->json(['success' => true, 'data' => Auth::user()->tenant]); // ← Debugging line, remove in production --- IGNORE ---

        return view('tenant.dashboard', $data);
    }
}