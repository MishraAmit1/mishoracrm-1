<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $staffList = $user->user_type === 'tenant_admin'
            ? User::where('tenant_id', $user->tenant_id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('tenant.calendar.index', ['staffList' => $staffList]);
    }

    // JSON feed consumed by FullCalendar — aggregates Followups, Tasks,
    // Reminders (all drag-to-reschedule-able) and Deal expected-close dates
    // (read-only) into one event list for the visible date range.
    public function events(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;
        $isAdmin  = $user->user_type === 'tenant_admin';

        // Non-admins always see only their own items. Admins see everyone by
        // default, but can narrow to one staff member via ?staff_id= (team view).
        $filterUserId = $isAdmin
            ? ($request->filled('staff_id') ? (int) $request->staff_id : null)
            : $user->id;

        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end   = Carbon::parse($request->query('end'))->endOfDay();

        $followups = Followup::where('tenant_id', $tenantId)
            ->when($filterUserId, fn($q) => $q->where('assigned_to', $filterUserId))
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();

        // Task has no BelongsToTenant scope — tenant_id must be filtered explicitly.
        $tasks = Task::where('tenant_id', $tenantId)
            ->when($filterUserId, fn($q) => $q->where('assigned_to', $filterUserId))
            ->whereBetween('due_at', [$start, $end])
            ->get();

        $reminders = Reminder::where('tenant_id', $tenantId)
            ->when($filterUserId, fn($q) => $q->where('user_id', $filterUserId))
            ->whereBetween('remind_at', [$start, $end])
            ->get();

        $deals = Deal::where('tenant_id', $tenantId)
            ->when($filterUserId, fn($q) => $q->where('assigned_to', $filterUserId))
            ->whereNotNull('expected_close_date')
            ->whereBetween('expected_close_date', [$start, $end])
            ->get();

        $statusColors = [
            'scheduled' => '#378ADD',
            'done'      => '#1D9E75',
            'missed'    => '#E05252',
        ];
        $priorityColors = config('task_fields.priorities', []);

        $events = collect()
            ->concat($followups->map(fn($f) => [
                'id'       => 'followup-' . $f->id,
                'title'    => ucfirst($f->type) . ' — ' . ($f->lead?->name ?? $f->contact?->name ?? 'Follow-up'),
                'start'    => $f->scheduled_at?->toIso8601String(),
                'color'    => $statusColors[$f->status] ?? '#6B7280',
                'url'      => route('tenant.followups.show', $f->id),
                'editable' => true,
                'extendedProps' => ['type' => 'followup', 'recordId' => $f->id],
            ]))
            ->concat($tasks->map(fn($t) => [
                'id'       => 'task-' . $t->id,
                'title'    => $t->title,
                'start'    => $t->due_at?->toDateString(),
                'allDay'   => true,
                'color'    => $priorityColors[$t->priority]['color'] ?? '#6B7280',
                'url'      => route('tenant.tasks.show', $t->id),
                'editable' => true,
                'extendedProps' => ['type' => 'task', 'recordId' => $t->id],
            ]))
            ->concat($reminders->map(fn($r) => [
                'id'       => 'reminder-' . $r->id,
                'title'    => $r->title,
                'start'    => $r->remind_at?->toIso8601String(),
                'color'    => $r->is_sent ? '#9CA3AF' : '#EF9F27',
                'url'      => null,
                'editable' => true,
                'extendedProps' => ['type' => 'reminder', 'recordId' => $r->id],
            ]))
            ->concat($deals->map(fn($d) => [
                'id'       => 'deal-' . $d->id,
                'title'    => 'Close: ' . $d->title,
                'start'    => $d->expected_close_date?->toDateString(),
                'allDay'   => true,
                'color'    => '#534AB7',
                'url'      => route('tenant.deals.show', $d->id),
                'editable' => false,
                'extendedProps' => ['type' => 'deal', 'recordId' => $d->id],
            ]))
            ->filter(fn($e) => !empty($e['start']))
            ->values();

        return response()->json($events);
    }

    // Drag-to-reschedule — moves a Followup/Task/Reminder to a new date/time.
    // Deals are read-only on the calendar (not reachable via this endpoint).
    public function reschedule(Request $request): JsonResponse
    {
        $request->validate([
            'type'  => ['required', 'in:followup,task,reminder'],
            'id'    => ['required', 'integer'],
            'start' => ['required', 'date'],
        ]);

        $user     = auth()->user();
        $tenantId = $user->tenant_id;
        $isAdmin  = $user->user_type === 'tenant_admin';
        $newStart = Carbon::parse($request->start);

        $updated = match ($request->type) {
            'followup' => Followup::where('tenant_id', $tenantId)
                ->when(!$isAdmin, fn($q) => $q->where('assigned_to', $user->id))
                ->where('id', $request->id)
                ->update(['scheduled_at' => $newStart]),
            'task' => Task::where('tenant_id', $tenantId)
                ->when(!$isAdmin, fn($q) => $q->where('assigned_to', $user->id))
                ->where('id', $request->id)
                ->update(['due_at' => $newStart]),
            'reminder' => Reminder::where('tenant_id', $tenantId)
                ->when(!$isAdmin, fn($q) => $q->where('user_id', $user->id))
                ->where('id', $request->id)
                ->update(['remind_at' => $newStart]),
        };

        if (!$updated) {
            return response()->json(['ok' => false, 'message' => 'Record not found or not editable by you.'], 404);
        }

        return response()->json(['ok' => true]);
    }

    // Quick-create — a minimal Followup or Task from a calendar date click.
    public function quickCreate(Request $request): JsonResponse
    {
        $request->validate([
            'kind'  => ['required', 'in:followup,task'],
            'title' => ['required_if:kind,task', 'nullable', 'string', 'max:255'],
            'type'  => ['required_if:kind,followup', 'nullable', 'in:call,email,whatsapp,meeting,other'],
            'date'  => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $userId   = auth()->id();

        if ($request->kind === 'followup') {
            Followup::create([
                'tenant_id'    => $tenantId,
                'assigned_to'  => $userId,
                'created_by'   => $userId,
                'type'         => $request->type,
                'scheduled_at' => $request->date,
                'notes'        => $request->notes,
                'status'       => 'scheduled',
            ]);
        } else {
            Task::create([
                'tenant_id'   => $tenantId,
                'title'       => $request->title,
                'description' => $request->notes,
                'status'      => 'pending',
                'priority'    => 'medium',
                'assigned_to' => $userId,
                'due_at'      => $request->date,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
