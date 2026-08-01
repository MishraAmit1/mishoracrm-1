@extends('layouts.app')
@section('title', 'Import Leads')

@push('styles')
<style>
.imp-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;padding:26px;max-width:640px}
.imp-drop{border:2px dashed var(--border-default);border-radius:12px;padding:36px 20px;text-align:center;transition:border-color .15s,background .15s}
.imp-drop.drag{border-color:var(--accent);background:var(--accent-dim)}
.imp-drop input[type=file]{display:none}
.imp-drop-label{cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--text-300)}
.imp-drop-label strong{color:var(--text-100);font-size:14px}
.imp-filename{margin-top:14px;font-size:13px;color:var(--text-200);display:none}
.imp-steps{display:flex;gap:18px;margin-bottom:22px}
.imp-step{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-300);font-weight:600}
.imp-step .imp-dot{width:22px;height:22px;border-radius:50%;background:var(--bg-elevated);border:1.5px solid var(--border-default);display:flex;align-items:center;justify-content:center;font-size:11px}
.imp-step.active .imp-dot{background:var(--accent);border-color:var(--accent);color:#fff}
.imp-step.active{color:var(--text-100)}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.leads.index') }}" style="color:var(--text-300);text-decoration:none">Leads</a>
            <span style="opacity:.4"> &rsaquo; </span> Import
        </div>
        <div class="page-title">Import Leads</div>
        <div class="page-sub">Upload a CSV or Excel file, map the columns, then confirm.</div>
    </div>
    <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary">Back</a>
</div>

<div class="imp-steps">
    <div class="imp-step active"><span class="imp-dot">1</span> Upload file</div>
    <div class="imp-step"><span class="imp-dot">2</span> Map columns</div>
    <div class="imp-step"><span class="imp-dot">3</span> Review results</div>
</div>

<div class="imp-card">
    <form method="POST" action="{{ route('tenant.leads.import.preview') }}" enctype="multipart/form-data" id="importForm">
        @csrf
        <div class="imp-drop" id="dropZone">
            <label class="imp-drop-label" for="fileInput">
                <svg width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <strong>Click to choose a file</strong>
                <span>or drag and drop — CSV or Excel (.xlsx), first row must be column headers</span>
            </label>
            <input type="file" name="file" id="fileInput" accept=".csv,.txt,.xlsx,.xls" required>
        </div>
        <div class="imp-filename" id="fileName"></div>

        @error('file')<div class="cf-field-error" style="margin-top:10px">{{ $message }}</div>@enderror

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px">
            <a href="{{ route('tenant.leads.import.template') }}" class="btn btn-secondary" style="font-size:12.5px">
                Download sample CSV
            </a>
            <button type="submit" class="btn btn-primary" id="uploadBtn">Continue</button>
        </div>
    </form>
</div>

<div class="imp-card" style="margin-top:16px">
    <div style="font-size:12px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Notes</div>
    <ul style="margin:0;padding-left:18px;color:var(--text-300);font-size:12.5px;line-height:1.8">
        <li><strong style="color:var(--text-100)">Name</strong> and <strong style="color:var(--text-100)">Phone</strong> columns are required.</li>
        <li>Rows matching an existing lead's phone or email are skipped automatically (not duplicated).</li>
        <li>Only the first sheet of an Excel file is imported.</li>
    </ul>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const drop  = document.getElementById('dropZone');
    const input = document.getElementById('fileInput');
    const name  = document.getElementById('fileName');

    input.addEventListener('change', () => {
        if (input.files[0]) { name.textContent = input.files[0].name; name.style.display = 'block'; }
    });
    ['dragover','dragenter'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('drag'); }));
    ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('drag'); }));
    drop.addEventListener('drop', e => {
        if (e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); }
    });

    document.getElementById('importForm').addEventListener('submit', function(){
        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('uploadBtn').textContent = 'Uploading...';
    });
})();
</script>
@endpush
