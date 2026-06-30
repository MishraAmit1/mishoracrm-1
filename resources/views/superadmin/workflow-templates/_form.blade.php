{{--
    Shared form partial for create & edit.
    Variables expected:
      $template  (existing WorkflowTemplate model — edit only)
      $action    (form POST URL)
      $method    ('POST' or 'PUT')
--}}

@php
    $isEdit   = isset($template);
    $features = $isEdit ? ($template->features ?? []) : [];
    $selColor = old('color', $isEdit ? $template->color : 'blue');
    $selIcon  = old('icon_type', $isEdit ? $template->icon_type : 'zap');

    $colors = ['blue','purple','green','orange','pink','teal','yellow'];
    $icons  = [
        'zap'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>',
        'chat'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
        'invoice' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>',
        'users'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
        'bell'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
        'star'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>',
        'ai'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>',
    ];
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    {{-- ── Basic Info ──────────────────────────────────────────────── --}}
    <div class="section-title">Basic Information</div>

    <div class="form-group">
        <label class="form-label">Title <span class="req">*</span></label>
        <input type="text" name="title"
               value="{{ old('title', $isEdit ? $template->title : '') }}"
               class="form-control @error('title') is-invalid @enderror"
               placeholder="e.g. Lead Auto-Qualifier AI" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label">Description <span class="req">*</span></label>
        <textarea name="description"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Kya karta hai yeh workflow? (1-2 lines)" required>{{ old('description', $isEdit ? $template->description : '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Category <span class="req">*</span></label>
            <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                <option value="">— Select —</option>
                @foreach(['lead','whatsapp','invoice','crm','hr','email','custom'] as $cat)
                    <option value="{{ $cat }}"
                        {{ old('category', $isEdit ? $template->category : '') === $cat ? 'selected' : '' }}>
                        {{ ucfirst($cat) }}
                    </option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label">Suitable For</label>
            <input type="text" name="suitable_for"
                   value="{{ old('suitable_for', $isEdit ? $template->suitable_for : '') }}"
                   class="form-control"
                   placeholder="e.g. Real Estate, EdTech">
            <div class="form-hint">Card pe badge ke roop mein dikhega</div>
        </div>
    </div>

    {{-- ── Visual ──────────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Visual Style</div>

    {{-- Icon picker --}}
    <div class="form-group">
        <label class="form-label">Icon <span class="req">*</span></label>
        <input type="hidden" name="icon_type" id="iconInput" value="{{ $selIcon }}">
        <div class="icon-pick">
            @foreach($icons as $key => $path)
            <button type="button"
                    class="icon-opt {{ $selIcon === $key ? 'selected' : '' }}"
                    data-icon="{{ $key }}"
                    title="{{ ucfirst($key) }}"
                    onclick="pickIcon('{{ $key }}', this)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    {!! $path !!}
                </svg>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Color picker --}}
    <div class="form-group">
        <label class="form-label">Card Color <span class="req">*</span></label>
        <input type="hidden" name="color" id="colorInput" value="{{ $selColor }}">
        <div class="color-pick">
            @foreach($colors as $col)
            <div class="color-swatch {{ $col }} {{ $selColor === $col ? 'selected' : '' }}"
                 title="{{ ucfirst($col) }}"
                 onclick="pickColor('{{ $col }}', this)">
            </div>
            @endforeach
        </div>
        <div class="form-hint" id="colorLabel">Selected: <strong>{{ ucfirst($selColor) }}</strong></div>
    </div>

    {{-- ── Features ────────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Feature Bullets</div>
    <div class="form-hint" style="margin-bottom:12px;">Up to 6 bullet points dikhte hain template card pe</div>

    <div class="feat-row-wrap" id="featWrap">
        @php
            $existingFeats = old('features', $features);
            $existingFeats = array_pad((array)$existingFeats, 3, '');
        @endphp
        @foreach($existingFeats as $i => $feat)
        <div class="feat-input-row">
            <input type="text" name="features[]" class="form-control"
                   value="{{ $feat }}"
                   placeholder="Feature {{ $i + 1 }}...">
            <button type="button" class="feat-del" onclick="removeFeat(this)" title="Remove">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endforeach
    </div>
    <button type="button" class="btn-add-feat" id="addFeatBtn" onclick="addFeat()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add bullet point
    </button>

    {{-- ── Settings ────────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Settings</div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order"
                   value="{{ old('sort_order', $isEdit ? $template->sort_order : 0) }}"
                   class="form-control" min="0">
            <div class="form-hint">0 = pehle dikhega</div>
        </div>

        <div class="form-group" style="display:flex;align-items:center;gap:12px;padding-top:28px;">
            <label class="toggle-wrap">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $isEdit ? $template->is_active : true) ? 'checked' : '' }}>
                <span class="toggle-track"></span>
                <span class="toggle-lbl">Active — tenants ko dikhega</span>
            </label>
        </div>
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Save Changes' : 'Create Template' }}
        </button>
        <a href="{{ route('superadmin.workflow-templates.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
function pickColor(color, el) {
    document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('colorInput').value = color;
    document.getElementById('colorLabel').innerHTML = 'Selected: <strong>' + color.charAt(0).toUpperCase() + color.slice(1) + '</strong>';
}

function pickIcon(icon, el) {
    document.querySelectorAll('.icon-opt').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('iconInput').value = icon;
}

function addFeat() {
    const wrap = document.getElementById('featWrap');
    if (wrap.children.length >= 6) return;
    const n = wrap.children.length + 1;
    const div = document.createElement('div');
    div.className = 'feat-input-row';
    div.innerHTML = `
        <input type="text" name="features[]" class="form-control" placeholder="Feature ${n}...">
        <button type="button" class="feat-del" onclick="removeFeat(this)" title="Remove">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>`;
    wrap.appendChild(div);
    if (wrap.children.length >= 6) {
        document.getElementById('addFeatBtn').style.display = 'none';
    }
}

function removeFeat(btn) {
    btn.closest('.feat-input-row').remove();
    document.getElementById('addFeatBtn').style.display = '';
}
</script>
@endpush
