<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Models\Attendance;
use App\Models\Staff;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    private function tenantSlug(): string
    {
        return auth()->user()->tenant->subdomain;
    }

    // ── Index : Monthly View ──────────────────────────────────────

    public function index(Request $request)
    {
        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $query = Attendance::with('staff')
            ->forMonth($year, $month);

        // Staff filter
        if ($request->filled('staff_id')) {
            $query->forStaff($request->staff_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('staff_id')
            ->paginate(30)
            ->withQueryString();

        // Summary counts
        $summary = Attendance::forMonth($year, $month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $staffList = Staff::with('user')->get();
        $total     = Attendance::forMonth($year, $month)->count();

        return view('tenant.attendances.index', compact(
            'attendances',
            'staffList',
            'summary',
            'year',
            'month',
            'total'
        ));
    }

    // ── Create / Store (Manual) ───────────────────────────────────

    public function create()
    {
        $staffList = Staff::with('user')->get();
        return view('tenant.attendances.create', compact('staffList'));
    }

    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();

        $data['clock_in']  = $data['clock_in']
            ? Carbon::parse($data['date'] . ' ' . $data['clock_in'])
            : null;
        $data['clock_out'] = $data['clock_out']
            ? Carbon::parse($data['date'] . ' ' . $data['clock_out'])
            : null;

        Attendance::create($data);

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', 'Attendance add ho gaya!');
    }

    // ── Edit / Update ─────────────────────────────────────────────

    public function edit(Attendance $attendance)
    {
        $staffList = Staff::with('user')->get();
        return view('tenant.attendances.create', compact('attendance', 'staffList'));
    }

    public function update(AttendanceRequest $request, Attendance $attendance)
    {
        $data = $request->validated();

        $data['clock_in']  = $data['clock_in']
            ? Carbon::parse($data['date'] . ' ' . $data['clock_in'])
            : null;
        $data['clock_out'] = $data['clock_out']
            ? Carbon::parse($data['date'] . ' ' . $data['clock_out'])
            : null;

        $attendance->update($data);

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', 'Attendance update ho gaya!');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Attendance delete ho gaya!');
    }

    // ── Bulk ──────────────────────────────────────────────────────

    public function bulk(Request $request)
    {
        $date      = $request->get('date', today()->toDateString());
        $staffList = Staff::with('user')->get();

        $existing = Attendance::forDate($date)
            ->get()
            ->keyBy('staff_id');

        return view('tenant.attendances.bulk', compact('staffList', 'date', 'existing'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'date'                    => 'required|date',
            'attendances'             => 'required|array',
            'attendances.*.status'    => 'required|in:present,absent,half_day,holiday,leave',
            'attendances.*.clock_in'  => 'nullable|date_format:H:i',
            'attendances.*.clock_out' => 'nullable|date_format:H:i',
        ]);

        $date = $request->date;

        DB::transaction(function () use ($request, $date) {
            foreach ($request->attendances as $staffId => $row) {
                Attendance::updateOrCreate(
                    ['staff_id' => $staffId, 'date' => $date],
                    [
                        'status'   => $row['status'],
                        'notes'    => $row['notes'] ?? null,
                        'clock_in' => ! empty($row['clock_in'])
                            ? Carbon::parse($date . ' ' . $row['clock_in']) : null,
                        'clock_out' => ! empty($row['clock_out'])
                            ? Carbon::parse($date . ' ' . $row['clock_out']) : null,
                    ]
                );
            }
        });

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', "Bulk attendance {$date} ke liye save ho gaya!");
    }

    // ── Clock In / Out ────────────────────────────────────────────

    public function clockView()
    {
        $staffList = Staff::with('user')->get();

        $today = Attendance::forDate(today())
            ->with('staff')
            ->get()
            ->keyBy('staff_id');

        return view('tenant.attendances.clock', compact('staffList', 'today'));
    }

    public function clockIn(Request $request)
    {
        $request->validate(['staff_id' => 'required|exists:staff,id']);

        $attendance = Attendance::firstOrCreate(
            ['staff_id' => $request->staff_id, 'date' => today()],
            ['status'   => 'present']
        );

        if ($attendance->clock_in) {
            return back()->with('error', 'Staff already clock in hai!');
        }

        $attendance->update(['clock_in' => now()]);

        // ✅ att_id aur staff_id redirect mein pass karo
        // JS isko read karke monitoring start karega
        return redirect()->route('tenant.attendances.clock', [
            'tenant'   => auth()->user()->tenant->subdomain,
            'att_id'   => $attendance->id,
            'staff_id' => $request->staff_id,
        ])->with('success', 'Clock In ho gaya! Screen monitoring shuru...');
    }

    public function clockOut(Request $request)
    {
        $request->validate(['staff_id' => 'required|exists:staff,id']);

        $attendance = Attendance::forDate(today())
            ->forStaff($request->staff_id)
            ->first();

        if (! $attendance?->clock_in) {
            return back()->with('error', 'Pehle clock in karo!');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Already clock out ho chuka hai!');
        }

        $attendance->update(['clock_out' => now()]);

        return redirect()->route('tenant.attendances.clock', [
            'tenant' => auth()->user()->tenant->subdomain,
        ])
            ->with('success', "Clock Out! Kaam kiya: {$attendance->worked_hours} hrs ✅")
            ->with('clocked_out', true); // JS localStorage clear karega
    }
}
