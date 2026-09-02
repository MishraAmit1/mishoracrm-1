{{--
    Shared form partial for create & edit.
    Variables expected:
      $plan    (existing Plan model — edit only)
      $action  (form POST URL)
      $method  ('POST' or 'PUT')
--}}

@php
    $isEdit   = isset($plan);
    $features = $isEdit ? ($plan->features ?? []) : [];
    $leadsVal = $features['leads'] ?? 100;
    $usersVal = $features['users'] ?? 5;
@endphp

<form action="{{ $action }}" method="POST" id="plan-form">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    {{-- ── Basic Info ─────────────────────────────────────────── --}}
    <div class="section-title">Basic Information</div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Plan Name <span class="req">*</span></label>
            <input type="text" name="name" id="plan-name"
                   value="{{ old('name', $isEdit ? $plan->name : '') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="e.g. Starter, Pro, Enterprise" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Slug <span class="req">*</span></label>
            <input type="text" name="slug" id="plan-slug"
                   value="{{ old('slug', $isEdit ? $plan->slug : '') }}"
                   class="form-control @error('slug') is-invalid @enderror"
                   placeholder="e.g. starter" required>
            <div class="form-hint">Only lowercase letters, numbers, hyphens</div>
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" name="description"
               value="{{ old('description', $isEdit ? $plan->description : '') }}"
               class="form-control" placeholder="Short tagline shown on pricing page">
    </div>

    {{-- ── Pricing ─────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Pricing</div>

    <div class="form-row-3">
        <div class="form-group">
            <label class="form-label">Monthly Price (₹) <span class="req">*</span></label>
            <div class="input-prefix-wrap">
                <span class="input-prefix">₹</span>
                <input type="number" name="monthly_price"
                       value="{{ old('monthly_price', $isEdit ? (int)$plan->monthly_price : '') }}"
                       class="form-control with-prefix @error('monthly_price') is-invalid @enderror"
                       placeholder="0" min="0" required>
            </div>
            @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">Yearly Price (₹) <span class="req">*</span></label>
            <div class="input-prefix-wrap">
                <span class="input-prefix">₹</span>
                <input type="number" name="yearly_price"
                       value="{{ old('yearly_price', $isEdit ? (int)$plan->yearly_price : '') }}"
                       class="form-control with-prefix @error('yearly_price') is-invalid @enderror"
                       placeholder="0" min="0" required>
            </div>
            @error('yearly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label">
                Discount (%)
                <span style="font-size:11px;font-weight:400;color:var(--text-400)"> — shown on plans page</span>
            </label>
            <div class="input-prefix-wrap">
                <input type="number" name="discount_percentage"
                       value="{{ old('discount_percentage', $isEdit ? $plan->discount_percentage : '') }}"
                       class="form-control @error('discount_percentage') is-invalid @enderror"
                       placeholder="e.g. 20" min="0" max="100" id="disc-input">
                <span class="input-suffix">%</span>
            </div>
            <div class="form-hint" id="disc-preview" style="color:var(--green);display:none"></div>
            @error('discount_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ── Razorpay ─────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Razorpay Plan IDs
        <span style="font-size:11px;font-weight:400;color:var(--text-400)"> — optional, for recurring billing</span>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Monthly Plan ID</label>
            <input type="text" name="razorpay_monthly_plan_id"
                   value="{{ old('razorpay_monthly_plan_id', $isEdit ? $plan->razorpay_monthly_plan_id : '') }}"
                   class="form-control" placeholder="plan_xxxxxxxxxx">
        </div>
        <div class="form-group">
            <label class="form-label">Yearly Plan ID</label>
            <input type="text" name="razorpay_yearly_plan_id"
                   value="{{ old('razorpay_yearly_plan_id', $isEdit ? $plan->razorpay_yearly_plan_id : '') }}"
                   class="form-control" placeholder="plan_xxxxxxxxxx">
        </div>
    </div>

    {{-- ── Features ────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Plan Features</div>

    {{-- Leads --}}
    <div class="form-group feat-group">
        <div class="feat-row">
            <div class="feat-label-col">
                <div class="feat-name">Leads</div>
                <div class="feat-sub">Max leads allowed</div>
            </div>
            <div class="feat-control-col">
                <label class="toggle-wrap">
                    <input type="checkbox" name="leads_unlimited" id="leads-unlimited"
                           {{ old('leads_unlimited', ($leadsVal == -1) ? '1' : '0') == '1' ? 'checked' : '' }}>
                    <span class="toggle-track"></span>
                    <span class="toggle-lbl">Unlimited</span>
                </label>
                <input type="number" name="leads_count" id="leads-count"
                       value="{{ old('leads_count', $leadsVal == -1 ? '' : $leadsVal) }}"
                       class="form-control feat-num" placeholder="e.g. 500"
                       min="1" {{ ($leadsVal == -1) ? 'disabled' : '' }}>
            </div>
        </div>
    </div>

    {{-- Users --}}
    <div class="form-group feat-group">
        <div class="feat-row">
            <div class="feat-label-col">
                <div class="feat-name">Team Members</div>
                <div class="feat-sub">Max users in workspace</div>
            </div>
            <div class="feat-control-col">
                <label class="toggle-wrap">
                    <input type="checkbox" name="users_unlimited" id="users-unlimited"
                           {{ old('users_unlimited', ($usersVal == -1) ? '1' : '0') == '1' ? 'checked' : '' }}>
                    <span class="toggle-track"></span>
                    <span class="toggle-lbl">Unlimited</span>
                </label>
                <input type="number" name="users_count" id="users-count"
                       value="{{ old('users_count', $usersVal == -1 ? '' : $usersVal) }}"
                       class="form-control feat-num" placeholder="e.g. 5"
                       min="1" {{ ($usersVal == -1) ? 'disabled' : '' }}>
            </div>
        </div>
    </div>

    {{-- Boolean features --}}
    @php
        $boolFeatures = [
            ['key' => 'feat_whatsapp',    'field' => 'whatsapp',     'label' => 'WhatsApp Integration', 'sub' => 'Send messages via WhatsApp'],
            ['key' => 'feat_reports',     'field' => 'reports',      'label' => 'Advanced Reports',     'sub' => 'Analytics & revenue reports'],
            ['key' => 'feat_social_leads','field' => 'social_leads', 'label' => 'Social Media Leads',   'sub' => 'Facebook / Instagram lead capture'],
        ];
        // Gated premium modules — single source of truth in config/modules.php
        foreach (config('modules') as $modKey => $mod) {
            $boolFeatures[] = ['key' => 'feat_' . $modKey, 'field' => $modKey, 'label' => $mod['label'], 'sub' => $mod['desc']];
        }
    @endphp
    @foreach($boolFeatures as $feat)
    @php $checked = old($feat['key'], ($features[$feat['field']] ?? false) ? '1' : '0') == '1'; @endphp
    <div class="form-group feat-group">
        <div class="feat-row">
            <div class="feat-label-col">
                <div class="feat-name">{{ $feat['label'] }}</div>
                <div class="feat-sub">{{ $feat['sub'] }}</div>
            </div>
            <div class="feat-control-col">
                <label class="toggle-wrap">
                    <input type="hidden" name="{{ $feat['key'] }}" value="0">
                    <input type="checkbox" name="{{ $feat['key'] }}" value="1" {{ $checked ? 'checked' : '' }}>
                    <span class="toggle-track"></span>
                    <span class="toggle-lbl" style="min-width:52px" id="lbl-{{ $feat['key'] }}">{{ $checked ? 'Included' : 'Not Included' }}</span>
                </label>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ── Settings ─────────────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:24px">Settings</div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Sort Order <span class="req">*</span></label>
            <input type="number" name="sort_order"
                   value="{{ old('sort_order', $isEdit ? $plan->sort_order : 0) }}"
                   class="form-control" min="0" required>
            <div class="form-hint">Lower = shown first on pricing page</div>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:12px;padding-top:28px">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $isEdit ? ($plan->is_active ? '1' : '0') : '1') == '1' ? 'checked' : '' }}
                   style="width:16px;height:16px;accent-color:var(--accent)">
            <label for="is_active" class="form-label" style="margin:0;cursor:pointer">
                Plan is Active <span style="font-size:12px;font-weight:400;color:var(--text-400)">(visible on pricing page)</span>
            </label>
        </div>
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Save Changes' : 'Create Plan' }}
        </button>
        <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
// Auto-generate slug from name (create only)
@if(!$isEdit)
document.getElementById('plan-name').addEventListener('input', function () {
    const slug = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
    document.getElementById('plan-slug').value = slug;
});
@endif

// Leads unlimited toggle
document.getElementById('leads-unlimited').addEventListener('change', function () {
    const cnt = document.getElementById('leads-count');
    cnt.disabled = this.checked;
    if (this.checked) cnt.value = '';
});

// Users unlimited toggle
document.getElementById('users-unlimited').addEventListener('change', function () {
    const cnt = document.getElementById('users-count');
    cnt.disabled = this.checked;
    if (this.checked) cnt.value = '';
});

// Boolean feature toggles — update label
document.querySelectorAll('.feat-group input[type=checkbox][name^="feat_"]').forEach(function (cb) {
    const lbl = document.getElementById('lbl-' + cb.name);
    if (!lbl) return;
    cb.addEventListener('change', function () {
        lbl.textContent = this.checked ? 'Included' : 'Not Included';
    });
});

// Discount preview
const discInput    = document.getElementById('disc-input');
const discPreview  = document.getElementById('disc-preview');
const monthlyInput = document.querySelector('input[name="monthly_price"]');
const yearlyInput  = document.querySelector('input[name="yearly_price"]');

function updateDiscPreview() {
    const pct     = parseFloat(discInput.value) || 0;
    const monthly = parseFloat(monthlyInput.value) || 0;
    const yearly  = parseFloat(yearlyInput.value)  || 0;
    if (pct > 0 && (monthly > 0 || yearly > 0)) {
        const dM = Math.round(monthly * (1 - pct/100));
        const dY = Math.round(yearly  * (1 - pct/100));
        let txt = pct + '% off → ';
        if (monthly > 0) txt += '₹' + dM.toLocaleString('en-IN') + '/mo';
        if (monthly > 0 && yearly > 0) txt += '  ·  ';
        if (yearly  > 0) txt += '₹' + dY.toLocaleString('en-IN') + '/yr';
        discPreview.textContent = txt;
        discPreview.style.display = '';
    } else {
        discPreview.style.display = 'none';
    }
}

[discInput, monthlyInput, yearlyInput].forEach(el => el.addEventListener('input', updateDiscPreview));
updateDiscPreview();
</script>
@endpush
