@extends('layouts.app')
@section('title', 'Add Custom Field')

@push('styles')
<style>
.cf-layout { display:grid; grid-template-columns:1fr 320px; gap:16px; align-items:start; }
@media(max-width:1024px) { .cf-layout { grid-template-columns:1fr; } }

.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.fc-section:last-child { border-bottom:none; }
.fc-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.fc-sub   { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }

.field { display:flex; flex-direction:column; gap:7px; margin-bottom:16px; }
.field:last-child { margin-bottom:0; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-error { font-size:12px; color:var(--red); }
.field-hint  { font-size:12px; color:var(--text-400); }

/* Type grid */
.type-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
@media(max-width:600px) { .type-grid { grid-template-columns:repeat(3,1fr); } }

.type-card {
    padding:12px 8px; border:1.5px solid var(--border-default);
    border-radius:var(--r-md); cursor:pointer;
    text-align:center; background:none; font-family:var(--font);
    transition:all .15s var(--ease);
}
.type-card:hover { border-color:var(--accent); background:var(--accent-dim); }
.type-card.selected { border-color:var(--accent); background:var(--accent-dim); }
.type-card input { display:none; }
.type-em   { font-size:20px; display:block; margin-bottom:5px; }
.type-name { font-size:10.5px; font-weight:600; color:var(--text-300); line-height:1.3; }
.type-card.selected .type-name { color:var(--accent); }

/* Options */
.options-list { display:flex; flex-direction:column; gap:6px; }
.option-row { display:flex; gap:6px; align-items:center; }
.option-input {
    flex:1; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13.5px; outline:none;
    transition:border-color .15s;
}
.option-input:focus { border-color:var(--accent); }
.del-opt {
    width:34px; height:36px; background:var(--red-dim);
    border:1.5px solid rgba(255,82,87,.25); border-radius:var(--r-sm);
    color:var(--red); cursor:pointer; display:flex;
    align-items:center; justify-content:center; flex-shrink:0; font-size:14px;
}
.add-opt-btn {
    display:flex; align-items:center; justify-content:center; gap:6px;
    padding:8px 14px; border:1.5px dashed var(--accent);
    border-radius:var(--r-sm); background:none; color:var(--accent);
    cursor:pointer; font-family:var(--font); font-size:13px; font-weight:600;
    width:100%; margin-top:6px; transition:background .15s;
}
.add-opt-btn:hover { background:var(--accent-dim); }

/* Toggle switch */
.sw-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.sw-row:last-child { border-bottom:none; }
.sw-info-title { font-size:13.5px; font-weight:600; color:var(--text-100); }
.sw-info-sub   { font-size:12px; color:var(--text-400); margin-top:2px; }
.sw { position:relative; width:42px; height:24px; flex-shrink:0; }
.sw input { opacity:0; width:0; height:0; position:absolute; }
.sw-track { position:absolute; inset:0; background:var(--border-default); border-radius:24px; cursor:pointer; transition:background .2s; }
.sw input:checked ~ .sw-track { background:var(--accent); }
.sw-thumb { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.2); transition:transform .2s; pointer-events:none; }
.sw input:checked ~ .sw-track .sw-thumb { transform:translateX(18px); }

/* Preview panel */
.preview-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.preview-head { padding:14px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; font-weight:700; color:var(--text-100); }
.preview-body { padding:20px; }
.preview-field { background:var(--bg-elevated); border-radius:var(--r-md); padding:16px; }
.preview-label { font-size:12px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.preview-input-mock {
    width:100%; padding:10px 13px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-400);
    font-family:var(--font); font-size:14px;
    pointer-events:none;
}

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:10px; }
</style>
@endpush

@section('content')

@php
    $modLabel = $modules[$module]['label'] ?? ucfirst($module);
    $selType  = old('field_type', 'text');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.custom-fields.index') }}" style="color:var(--text-300);text-decoration:none">Custom Fields</a>
            <span style="margin:0 6px">›</span>
            <a href="{{ route('tenant.custom-fields.module', $module) }}" style="color:var(--text-300);text-decoration:none">{{ $modLabel }}</a>
            <span style="margin:0 6px">›</span>
            Add Field
        </div>
        <div class="page-title">Add Custom Field</div>
        <div class="page-sub">Add a new field to the <strong>{{ $modLabel }}</strong> module</div>
    </div>
    <a href="{{ route('tenant.custom-fields.module', $module) }}" class="btn btn-secondary">← Back</a>
</div>

<div class="cf-layout">

    {{-- ── Form ────────────────────────────────────────────────── --}}
    <div class="form-card">
        <form method="POST" action="{{ route('tenant.custom-fields.store', $module) }}" id="cfForm">
            @csrf

            {{-- Basic info --}}
            <div class="fc-section">
                <div class="fc-title">Field Information</div>
                <div class="fc-sub">Field ka naam aur basic details</div>

                <div class="field">
                    <label class="field-label">Field Label <span class="req">*</span></label>
                    <input type="text" name="label" id="labelInput"
                           class="field-input {{ $errors->has('label') ? 'is-error':'' }}"
                           placeholder="e.g. Budget Range, Lead Score..."
                           value="{{ old('label') }}"
                           oninput="updatePreview()" required/>
                    @error('label') <span class="field-error">{{ $message }}</span> @enderror
                    <div class="field-hint">
                        Field Key (auto):
                        <code id="keyPreview" style="color:var(--accent);font-family:var(--mono)">—</code>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label">Placeholder Text</label>
                    <input type="text" name="placeholder" id="placeholderInput"
                           class="field-input"
                           placeholder="Hint text shown inside the field..."
                           value="{{ old('placeholder') }}"
                           oninput="updatePreview()"/>
                </div>

                <div class="field">
                    <label class="field-label">Default Value</label>
                    <input type="text" name="default_value"
                           class="field-input"
                           placeholder="Pre-filled value (optional)"
                           value="{{ old('default_value') }}"/>
                </div>
            </div>

            {{-- Field type --}}
            <div class="fc-section">
                <div class="fc-title">Field Type <span class="req">*</span></div>
                <div class="fc-sub">Kis tarah ka input chahiye</div>

                <input type="hidden" name="field_type" id="fieldTypeInput" value="{{ $selType }}" required/>

                <div class="type-grid" id="typeGrid">
                    @foreach($fieldTypes as $key => $ft)
                    <div class="type-card {{ $selType === $key ? 'selected':'' }}"
                         id="tc_{{ $key }}"
                         onclick="selectType('{{ $key }}')">
                        <input type="radio" value="{{ $key }}" {{ $selType === $key ? 'checked':'' }}/>
                        <span class="type-em">{{ $ft['icon'] }}</span>
                        <div class="type-name">{{ $ft['label'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Options (dropdown/multi_select) --}}
            <div class="fc-section" id="optionsSection"
                 style="{{ in_array($selType,['dropdown','multi_select']) ? '':'display:none' }}">
                <div class="fc-title">Dropdown Options <span class="req">*</span></div>
                <div class="fc-sub">User ko kitne choices milenge</div>

                <div class="options-list" id="optionsList">
                    @if(old('options'))
                        @foreach(old('options') as $opt)
                        <div class="option-row">
                            <input type="text" name="options[]" class="option-input"
                                   value="{{ $opt }}" placeholder="Option value"/>
                            <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>
                        </div>
                        @endforeach
                    @else
                    <div class="option-row">
                        <input type="text" name="options[]" class="option-input" placeholder="Option 1"/>
                        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>
                    </div>
                    <div class="option-row">
                        <input type="text" name="options[]" class="option-input" placeholder="Option 2"/>
                        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>
                    </div>
                    <div class="option-row">
                        <input type="text" name="options[]" class="option-input" placeholder="Option 3"/>
                        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>
                    </div>
                    @endif
                </div>

                <button type="button" class="add-opt-btn" onclick="addOpt()">
                    + Add Option
                </button>
            </div>

            {{-- Settings --}}
            <div class="fc-section">
                <div class="fc-title">Field Settings</div>
                <div class="fc-sub">Field ka behavior configure karo</div>

                <div style="background:var(--bg-elevated);border-radius:var(--r-sm);padding:4px 16px">
                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Required Field</div>
                            <div class="sw-info-sub">User ko yeh field fill karna zaroori hoga</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>
                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Show in List View</div>
                            <div class="sw-info-sub">{{ $modLabel }} ki table mein column ke roop mein dikhega</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="show_in_list" value="1" {{ old('show_in_list') ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>
                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Show in Filter</div>
                            <div class="sw-info-sub">Filter bar mein yeh field available hoga</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="show_in_filter" value="1" {{ old('show_in_filter') ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('tenant.custom-fields.module', $module) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Field
                </button>
            </div>
        </form>
    </div>

    {{-- ── Preview panel ────────────────────────────────────────── --}}
    <div class="preview-card">
        <div class="preview-head">👁 Live Preview</div>
        <div class="preview-body">
            <div class="preview-field">
                <div class="preview-label" id="prevLabel">Field Label</div>
                <div id="prevInput">
                    <input class="preview-input-mock" readonly placeholder="Field preview..."/>
                </div>
            </div>
            <div style="margin-top:16px;padding:12px;background:var(--bg-elevated);border-radius:var(--r-sm)">
                <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">Field Info</div>
                <div style="display:flex;flex-direction:column;gap:5px;font-size:12px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-300)">Module</span>
                        <span style="color:var(--text-100);font-weight:600">{{ $modLabel }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-300)">Type</span>
                        <span id="prevType" style="color:var(--accent);font-weight:600;font-family:var(--mono)">text</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-300)">Key</span>
                        <span id="prevKey" style="color:var(--accent);font-weight:600;font-family:var(--mono)">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// ── Type selection ────────────────────────────────────────────────
function selectType(key) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('tc_' + key)?.classList.add('selected');
    document.getElementById('fieldTypeInput').value = key;

    // Show/hide options
    const needsOpts = ['dropdown','multi_select'].includes(key);
    document.getElementById('optionsSection').style.display = needsOpts ? 'block' : 'none';

    // Update preview
    document.getElementById('prevType').textContent = key;
    renderPreviewInput(key);
}

// ── Preview input based on type ───────────────────────────────────
function renderPreviewInput(type) {
    const ph    = document.getElementById('placeholderInput')?.value || 'Enter value...';
    const label = document.getElementById('labelInput')?.value || 'Field Label';
    const wrap  = document.getElementById('prevInput');

    const baseStyle = 'width:100%;padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-400);font-family:var(--font);font-size:14px;pointer-events:none;box-sizing:border-box';

    const map = {
        text:         `<input style="${baseStyle}" placeholder="${ph}" readonly/>`,
        number:       `<input type="number" style="${baseStyle}" placeholder="0" readonly/>`,
        email:        `<input type="email" style="${baseStyle}" placeholder="email@example.com" readonly/>`,
        phone:        `<input type="tel" style="${baseStyle}" placeholder="+91 98765 43210" readonly/>`,
        url:          `<input type="url" style="${baseStyle}" placeholder="https://..." readonly/>`,
        date:         `<input type="date" style="${baseStyle}" readonly/>`,
        datetime:     `<input type="datetime-local" style="${baseStyle}" readonly/>`,
        textarea:     `<textarea style="${baseStyle};min-height:80px;resize:none" placeholder="${ph}" readonly></textarea>`,
        dropdown:     `<select style="${baseStyle};-webkit-appearance:none" disabled><option>— Select —</option></select>`,
        multi_select: `<div style="padding:10px 14px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);display:flex;flex-wrap:wrap;gap:8px;color:var(--text-400);font-size:13px">☑ Option 1 &nbsp; ☑ Option 2 &nbsp; ☑ Option 3</div>`,
        checkbox:     `<label style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm)"><input type="checkbox" disabled style="width:16px;height:16px;accent-color:var(--accent)"/><span style="color:var(--text-300);font-size:13.5px">${label}</span></label>`,
    };

    wrap.innerHTML = map[type] || map['text'];
}

// ── Update preview ────────────────────────────────────────────────
function updatePreview() {
    const label = document.getElementById('labelInput').value || 'Field Label';
    const key   = label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');

    document.getElementById('prevLabel').textContent  = label + (document.querySelector('[name="is_required"]')?.checked ? ' *' : '');
    document.getElementById('prevKey').textContent    = key || '—';
    document.getElementById('keyPreview').textContent = key || '—';

    const type = document.getElementById('fieldTypeInput').value;
    renderPreviewInput(type);
}

// ── Options ───────────────────────────────────────────────────────
function addOpt() {
    const list  = document.getElementById('optionsList');
    const count = list.querySelectorAll('.option-row').length + 1;
    const row   = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = `
        <input type="text" name="options[]" class="option-input" placeholder="Option ${count}"/>
        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>`;
    list.appendChild(row);
    row.querySelector('input').focus();
}

function removeOpt(btn) {
    const list = document.getElementById('optionsList');
    if (list.querySelectorAll('.option-row').length > 1) {
        btn.closest('.option-row').remove();
    } else {
        alert('At least one option is required.');
    }
}

// ── Init ──────────────────────────────────────────────────────────
updatePreview();
renderPreviewInput('{{ $selType }}');
</script>
@endpush