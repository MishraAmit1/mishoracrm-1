@extends('layouts.app')
@section('title', 'Bulk Attendance')

@section('content')
@php $tenantSlug = auth()->user()->tenant->subdomain; @endphp

<div class="page-head">
    <div>
        <div class="page-title">📝 Bulk Attendance</div>
        <div class="page-sub">Ek din mein sabhi staff ka attendance ek saath mark karo</div>
    </div>
    <div class="page-actions">
        {{-- Quick date buttons --}}
        <a href="{{ route('tenant.attendances.bulk', ['tenant' => $tenantSlug, 'date' => today()->subDay()->toDateString()]) }}"
           class="btn btn-secondary">← Yesterday</a>
        <a href="{{ route('tenant.attendances.bulk', ['tenant' => $tenantSlug, 'date' => today()->toDateString()]) }}"
           class="btn btn-secondary">Today</a>
        <a href="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">← Back</a>
    </div>
</div>

{{-- Date picker --}}
<form method="GET"
      action="{{ route('tenant.attendances.bulk', ['tenant' => $tenantSlug]) }}"
      style="margin-bottom:20px">
    <div class="filter-bar">
        <input type="date" name="date" class="filter-input" value="{{ $date }}" style="height:40px">
        <button type="submit" class="btn btn-secondary">Load</button>
    </div>
</form>

<form action="{{ route('tenant.attendances.bulk.store', ['tenant' => $tenantSlug]) }}" method="POST">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    {{-- Quick mark all buttons --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;align-items:center">
        <span style="font-size:13px;color:var(--text-300);margin-right:4px">Mark All:</span>
        @foreach(['present','absent','half_day','holiday','leave'] as $st)
        <button type="button" onclick="markAll('{{ $st }}')"
                class="btn btn-secondary" style="font-size:12px;padding:5px 12px">
            {{ ucfirst(str_replace('_',' ',$st)) }}
        </button>
        @endforeach
    </div>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th style="width:160px">Status</th>
                        <th style="width:120px">Clock In</th>
                        <th style="width:120px">Clock Out</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staffList as $staff)
                    @php $att = $existing[$staff->id] ?? null; @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:32px;height:32px;border-radius:50%;
                                            background:var(--accent-dim);color:var(--accent);
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:13px;font-weight:700;flex-shrink:0">
                                    {{ strtoupper(substr($staff->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">
                                        {{ $staff->name }}
                                    </div>
                                    @if($staff->designation)
                                    <div style="font-size:11.5px;color:var(--text-400)">{{ $staff->designation }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <select name="attendances[{{ $staff->id }}][status]"
                                    class="filter-input status-select"
                                    style="width:100%;height:34px;font-size:12.5px"
                                    data-staff="{{ $staff->id }}">
                                @foreach(['present','absent','half_day','holiday','leave'] as $st)
                                    <option value="{{ $st }}"
                                        @selected(($att?->status ?? 'present') == $st)>
                                        {{ ucfirst(str_replace('_',' ',$st)) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="time"
                                   name="attendances[{{ $staff->id }}][clock_in]"
                                   class="filter-input"
                                   style="width:100%;height:34px;font-size:12.5px"
                                   value="{{ $att?->clock_in?->format('H:i') ?? '' }}">
                        </td>
                        <td>
                            <input type="time"
                                   name="attendances[{{ $staff->id }}][clock_out]"
                                   class="filter-input"
                                   style="width:100%;height:34px;font-size:12.5px"
                                   value="{{ $att?->clock_out?->format('H:i') ?? '' }}">
                        </td>
                        <td>
                            <input type="text"
                                   name="attendances[{{ $staff->id }}][notes]"
                                   class="filter-input"
                                   style="width:100%;height:34px;font-size:12.5px"
                                   placeholder="Optional..."
                                   value="{{ $att?->notes ?? '' }}">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:16px 20px;border-top:1px solid var(--border-subtle);display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">
                💾 Save All — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
            </button>
            <a href="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug]) }}"
               class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
function markAll(status) {
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.value = status;
    });
}
</script>
@endpush