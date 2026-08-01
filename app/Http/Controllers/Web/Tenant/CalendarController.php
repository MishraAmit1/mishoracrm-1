<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Followup;
use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('tenant.calendar.index');
    }

    // JSON feed consumed by FullCalendar — aggregates Followups, Tasks and
    // Reminders into one event list for the visible date range.
    public function events(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;
        $isAdmin  = $user->user_type === 'tenant_admin';

        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end   = Carbon::parse($request->query('end'))->endOfDay();

        $followups = Followup::where('tenant_id', $tenantId)
            ->when(!$isAdmin, fn($q) => $q->where('assigned_to', $user->id))
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();

        // Task has no BelongsToTenant scope — tenant_id must be filtered explicitly.
        $tasks = Task::where('tenant_id', $tenantId)
            ->when(!$isAdmin, fn($q) => $q->where('assigned_to', $user->id))
            ->whereBetween('due_at', [$start, $end])
            ->get();

        $reminders = Reminder::where('tenant_id', $tenantId)
            ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('remind_at', [$start, $end])
            ->get();

        $statusColors = [
            'scheduled' => '#378ADD',
            'done'      => '#1D9E75',
            'missed'    => '#E05252',
        ];
        $priorityColors = config('task_fields.priorities', []);

        $events = collect()
            ->concat($followups->map(fn($f) => [
                'id'    => 'followup-' . $f->id,
                'title' => ucfirst($f->type) . ' — ' . ($f->lead?->name ?? $f->contact?->name ?? 'Follow-up'),
                'start' => $f->scheduled_at?->toIso8601String(),
                'color' => $statusColors[$f->status] ?? '#6B7280',
                'url'   => route('tenant.followups.show', $f->id),
            ]))
            ->concat($tasks->map(fn($t) => [
                'id'      => 'task-' . $t->id,
                'title'   => $t->title,
                'start'   => $t->due_at?->toDateString(),
                'allDay'  => true,
                'color'   => $priorityColors[$t->priority]['color'] ?? '#6B7280',
                'url'     => route('tenant.tasks.show', $t->id),
            ]))
            ->concat($reminders->map(fn($r) => [
                'id'    => 'reminder-' . $r->id,
                'title' => $r->title,
                'start' => $r->remind_at?->toIso8601String(),
                'color' => $r->is_sent ? '#9CA3AF' : '#EF9F27',
                'url'   => null,
            ]))
            ->filter(fn($e) => !empty($e['start']))
            ->values();

        return response()->json($events);
    }
}
