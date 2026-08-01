@extends('layouts.app')
@section('title', 'Map Columns — Import Leads')

@push('styles')
<style>
.imp-steps{display:flex;gap:18px;margin-bottom:22px}
.imp-step{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-300);font-weight:600}
.imp-step .imp-dot{width:22px;height:22px;border-radius:50%;background:var(--bg-elevated);border:1.5px solid var(--border-default);display:flex;align-items:center;justify-content:center;font-size:11px}
.imp-step.active .imp-dot{background:var(--accent);border-color:var(--accent);color:#fff}
.imp-step.active{color:var(--text-100)}
.imp-step.done .imp-dot{background:var(--green,#1D9E75);border-color:var(--green,#1D9E75);color:#fff}
.map-table-wrap{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;overflow-x:auto}
.map-table{width:100%;border-collapse:collapse;min-width:720px}
.map-table th{padding:10px 12px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle);background:var(--bg-elevated);white-space:nowrap}
.map-table td{padding:10px 12px;font-size:12.5px;color:var(--text-100);border-bottom:1px solid var(--border-subtle);vertical-align:top}
.map-col-name{font-weight:700;color:var(--text-100)}
.map-sample{color:var(--text-400);font-size:11.5px;margin-top:3px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.map-select{width:100%;min-width:180px;padding:6px 9px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:7px;color:var(--text-100);font-family:var(--font);font-size:12.5px}
optgroup{font-weight:700}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.leads.index') }}" style="color:var(--text-300);text-decoration:none">Leads</a>
            <span style="opacity:.4"> &rsaquo; </span> Import
        </div>
        <div class="page-title">Map Columns</div>
        <div class="page-sub">Tell us which file column goes to which lead field.</div>
    </div>
    <a href="{{ route('tenant.leads.import') }}" class="btn btn-secondary">Start over</a>
</div>

<div class="imp-steps">
    <div class="imp-step done"><span class="imp-dot">&#10003;</span> Upload file</div>
    <div class="imp-step active"><span class="imp-dot">2</span> Map columns</div>
    <div class="imp-step"><span class="imp-dot">3</span> Review results</div>
</div>

@if ($errors->any())
<div class="flash flash-error" style="margin-bottom:16px;position:static">
    <span class="flash-text">{{ $errors->first() }}</span>
</div>
@endif

<form method="POST" action="{{ route('tenant.leads.import.confirm') }}" id="mapForm">
    @csrf
    <input type="hidden" name="import_token" value="{{ $importToken }}">

    <div class="map-table-wrap">
        <table class="map-table">
            <thead>
                <tr>
                    <th>File Column</th>
                    <th>Sample Data</th>
                    <th>Import As</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($headers as $i => $header)
                <tr>
                    <td>
                        <div class="map-col-name">{{ $header !== '' ? $header : '(column ' . ($i + 1) . ')' }}</div>
                    </td>
                    <td>
                        @foreach ($previewRows as $row)
                            <div class="map-sample">{{ $row[$i] ?? '' }}</div>
                        @endforeach
                    </td>
                    <td>
                        <select name="mapping[{{ $i }}]" class="map-select">
                            <option value="">— Do not import —</option>
                            <optgroup label="Lead Fields">
                                @foreach ($fieldOptions as $val => $label)
                                <option value="{{ $val }}" @selected(strtolower($header) === $val || strtolower(str_replace(' ', '_', $header)) === $val)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                            @if (count($customOptions))
                            <optgroup label="Custom Fields">
                                @foreach ($customOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </optgroup>
                            @endif
                        </select>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px">
        <div style="font-size:12.5px;color:var(--text-300)">Rows matching an existing lead's phone/email will be skipped.</div>
        <button type="submit" class="btn btn-primary" id="confirmBtn">Import Leads</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('mapForm').addEventListener('submit', function(e){
    const selects = Array.from(this.querySelectorAll('select[name^="mapping"]')).map(s => s.value);
    if (!selects.includes('name') || !selects.includes('phone')) {
        e.preventDefault();
        showToast('Map a column to both Name and Phone before importing.', 'error');
        return;
    }
    document.getElementById('confirmBtn').disabled = true;
    document.getElementById('confirmBtn').textContent = 'Importing...';
});
</script>
@endpush
