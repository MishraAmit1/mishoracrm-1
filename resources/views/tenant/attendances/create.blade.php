@extends('layouts.app')
@section('title', isset($attendance) ? 'Edit Attendance' : 'Manual Attendance')

@section('content')
@php $tenantSlug = auth()->user()->tenant->subdomain; @endphp

<div class="page-head">
    <div>
        <div class="page-title">{{ isset($attendance) ? '✏️ Edit' : '➕ Manual' }} Attendance</div>
        <div class="page-sub">Staff ka attendance manually enter karo</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">← Back</a>
    </div>
</div>

<div class="card" style="max-width:620px">
    <div style="padding:24px">
        <form action="{{ isset($attendance)
            ? route('tenant.attendances.update', ['tenant' => $tenantSlug, 'attendance' => $attendance->id])
            : route('tenant.attendances.store',  ['tenant' => $tenantSlug]) }}"
            method="POST">
            @csrf
            @isset($attendance) @method('PUT') @endisset

            {{-- Staff --}}
            <div style="margin-bottom:18px">
                <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                    Staff Member *
                </label>
                <select name="staff_id" class="filter-input" style="width:100%;height:40px">
                    <option value="">— Select Staff —</option>
                    @foreach($staffList as $s)
                        <option value="{{ $s->id }}"
                            @selected(old('staff_id', $attendance?->staff_id ?? '') == $s->id)>
                            {{ $s->name }}
                            @if($s->designation) ({{ $s->designation }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('staff_id')
                <div style="font-size:12px;color:var(--red);margin-top:4px">{{ $message }}</div>
                @enderror
            </div>

            {{-- Date --}}
            <div style="margin-bottom:18px">
                <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                    Date *
                </label>
                <input type="date" name="date" class="filter-input" style="width:100%;height:40px"
                    value="{{ old('date', $attendance?->date?->toDateString() ?? today()->toDateString()) }}">
                @error('date')
                <div style="font-size:12px;color:var(--red);margin-top:4px">{{ $message }}</div>
                @enderror
            </div>

            {{-- Status --}}
            <div style="margin-bottom:18px">
                <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                    Status *
                </label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @foreach([
                        'present'  => ['Present',   'green'],
                        'absent'   => ['Absent',    'red'],
                        'half_day' => ['Half Day',  'amber'],
                        'leave'    => ['Leave',     'blue'],
                        'holiday'  => ['Holiday',   'purple'],
                    ] as $val => [$label, $color])
                    <label style="cursor:pointer">
                        <input type="radio" name="status" value="{{ $val }}" style="display:none"
                               class="status-radio"
                               @checked(old('status', $attendance?->status ?? 'present') == $val)>
                        <span class="status-badge badge-{{ $color }} status-opt"
                              style="cursor:pointer;padding:6px 14px;font-size:12.5px">
                            {{ $label }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Clock In / Out --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">
                <div>
                    <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                        Clock In
                    </label>
                    <input type="time" name="clock_in" class="filter-input" style="width:100%;height:40px"
                        value="{{ old('clock_in', $attendance?->clock_in?->format('H:i') ?? '') }}">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                        Clock Out
                    </label>
                    <input type="time" name="clock_out" class="filter-input" style="width:100%;height:40px"
                        value="{{ old('clock_out', $attendance?->clock_out?->format('H:i') ?? '') }}">
                </div>
            </div>

            {{-- Notes --}}
            <div style="margin-bottom:24px">
                <label style="font-size:13px;font-weight:600;color:var(--text-200);display:block;margin-bottom:6px">
                    Notes
                </label>
                <textarea name="notes" class="filter-input"
                    style="width:100%;height:90px;resize:vertical;padding-top:10px"
                    placeholder="Optional notes...">{{ old('notes', $attendance?->notes ?? '') }}</textarea>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">
                    {{ isset($attendance) ? 'Update' : 'Save' }} Attendance
                </button>
                <a href="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug]) }}"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Visual radio buttons for status
document.querySelectorAll('.status-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.status-opt').forEach(el => {
            el.style.opacity = '0.45';
            el.style.transform = 'scale(0.97)';
        });
        radio.nextElementSibling.style.opacity = '1';
        radio.nextElementSibling.style.transform = 'scale(1)';
    });

    // Init state
    if (radio.checked) {
        radio.nextElementSibling.style.opacity = '1';
    } else {
        radio.nextElementSibling.style.opacity = '0.45';
        radio.nextElementSibling.style.transform = 'scale(0.97)';
    }
});
</script>
@endpush