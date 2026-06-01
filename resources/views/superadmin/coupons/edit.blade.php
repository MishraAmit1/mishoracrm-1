@extends('layouts.app')
@section('title', 'Edit Coupon — ' . $coupon->code)

@push('styles')
<style>
.form-wrap { max-width:640px; margin:0 auto; }
.form-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); padding:28px; }
.form-card h2 { font-size:18px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.form-card .sub { font-size:13px; color:var(--text-400); margin-bottom:24px; }
.form-group { margin-bottom:18px; }
.form-label { display:block; font-size:13px; font-weight:600; color:var(--text-300); margin-bottom:6px; }
.form-label span.req { color:#ef4444; margin-left:2px; }
.form-control {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); color:var(--text-100); font-size:13.5px; outline:none;
    box-sizing:border-box;
}
.form-control:focus { border-color:var(--accent); }
.form-control.is-invalid { border-color:#ef4444; }
.invalid-feedback { font-size:12px; color:#ef4444; margin-top:4px; }
.form-hint { font-size:12px; color:var(--text-400); margin-top:4px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:540px){ .form-row { grid-template-columns:1fr; } }
.form-footer { display:flex; gap:10px; margin-top:24px; }
.btn-primary { padding:10px 22px; background:var(--accent); color:#fff; border:none; border-radius:var(--r-md); font-size:14px; font-weight:700; cursor:pointer; }
.btn-secondary { padding:10px 18px; background:var(--bg-input); color:var(--text-200); border:1px solid var(--border-subtle); border-radius:var(--r-md); font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; }
.back-link { display:flex; align-items:center; gap:6px; color:var(--text-400); font-size:13px; text-decoration:none; margin-bottom:18px; }
.back-link:hover { color:var(--text-100); }
.used-info { background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:10px 14px; font-size:13px; color:var(--text-300); margin-bottom:18px; }
.used-info strong { color:var(--text-100); }
#users-field { display:none; }
.user-check-list {
    max-height:260px; overflow-y:auto;
    border:1px solid var(--border-subtle); border-radius:var(--r-md);
    background:var(--bg-input);
}
.user-check-item {
    display:flex; align-items:center; gap:10px;
    padding:9px 14px; border-bottom:1px solid var(--border-subtle);
    cursor:pointer; transition:background 0.12s;
}
.user-check-item:last-child { border-bottom:none; }
.user-check-item:hover { background:var(--bg-hover); }
.user-check-item input[type=checkbox] { width:15px; height:15px; accent-color:var(--accent); flex-shrink:0; cursor:pointer; }
.user-check-item .uinfo { flex:1; }
.user-check-item .uname { font-size:13.5px; font-weight:600; color:var(--text-100); }
.user-check-item .umeta { font-size:12px; color:var(--text-400); margin-top:1px; }
.user-check-actions { display:flex; gap:8px; margin-bottom:8px; }
.user-check-actions a { font-size:12px; color:var(--accent); cursor:pointer; text-decoration:none; }
.selected-count { font-size:12px; color:var(--text-400); margin-top:6px; }
.selected-count span { color:var(--accent); font-weight:700; }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="form-wrap">

    <a href="{{ route('superadmin.coupons.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Coupons
    </a>

    <div class="form-card">
        <h2>Edit Coupon</h2>
        <div class="sub">Code: <strong style="color:var(--text-100);font-family:monospace">{{ $coupon->code }}</strong></div>

        @if($coupon->used_count > 0)
        <div class="used-info">
            This coupon has been used <strong>{{ $coupon->used_count }} time(s)</strong>.
            Changing the discount will not affect past subscriptions.
        </div>
        @endif

        <form action="{{ route('superadmin.coupons.update', $coupon) }}" method="POST">
            @csrf @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Coupon Code <span class="req">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control @error('code') is-invalid @enderror"
                           style="text-transform:uppercase" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Coupon Name <span class="req">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $coupon->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" value="{{ old('description', $coupon->description) }}" class="form-control"
                       placeholder="Optional short note">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Discount Type <span class="req">*</span></label>
                    <select name="type" id="type-select" class="form-control" required>
                        <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed"      {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" id="discount-val-label">
                        Discount Value {{ $coupon->type === 'percentage' ? '(%)' : '(₹)' }} <span class="req">*</span>
                    </label>
                    <input type="number" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}"
                           class="form-control @error('discount_value') is-invalid @enderror"
                           min="0.01" step="0.01" required>
                    @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group" id="max-discount-field">
                <label class="form-label">Max Discount Cap (₹)</label>
                <input type="number" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}"
                       class="form-control" placeholder="Leave blank for no cap" min="0" step="0.01">
                <div class="form-hint">For percentage type — cap the maximum discount amount</div>
            </div>

            <div class="form-group">
                <label class="form-label">Applicable To <span class="req">*</span></label>
                <select name="applicable_to" id="applicable-select" class="form-control" required>
                    <option value="all"           {{ old('applicable_to', $coupon->applicable_to) === 'all' ? 'selected' : '' }}>All Users</option>
                    <option value="specific_user" {{ old('applicable_to', $coupon->applicable_to) === 'specific_user' ? 'selected' : '' }}>Specific User(s)</option>
                </select>
            </div>

            <div class="form-group" id="users-field">
                <label class="form-label">Select Users <span class="req">*</span></label>
                <input type="text" id="user-search" class="form-control" placeholder="Search by name, email or company…"
                       style="margin-bottom:8px" autocomplete="off">
                <div class="user-check-actions">
                    <a id="check-all">Select All</a>
                    <a id="uncheck-all">Clear All</a>
                </div>
                @php $assignedIds = old('user_ids', $coupon->users->pluck('id')->toArray()); @endphp
                <div class="user-check-list" id="user-check-list">
                    @foreach($users as $u)
                    <label class="user-check-item">
                        <input type="checkbox" name="user_ids[]" value="{{ $u->id }}"
                            {{ in_array($u->id, $assignedIds) ? 'checked' : '' }}>
                        <div class="uinfo">
                            <div class="uname">{{ $u->name }}</div>
                            <div class="umeta">
                                {{ $u->email }}
                                @if($u->tenant) · {{ $u->tenant->name }} @endif
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                <div class="selected-count">Selected: <span id="sel-count">0</span> user(s)</div>
                @error('user_ids')<div class="invalid-feedback" style="display:block">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Max Uses</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}"
                           class="form-control" placeholder="Unlimited" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:10px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $coupon->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                       style="width:16px;height:16px;accent-color:var(--accent)">
                <label for="is_active" class="form-label" style="margin:0;cursor:pointer">Active</label>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('superadmin.coupons.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
const typeSelect   = document.getElementById('type-select');
const valLabel     = document.getElementById('discount-val-label');
const maxDiscField = document.getElementById('max-discount-field');
const appSelect    = document.getElementById('applicable-select');
const usersField   = document.getElementById('users-field');
const selCount     = document.getElementById('sel-count');

function updateTypeUI() {
    const isPerc = typeSelect.value === 'percentage';
    valLabel.innerHTML = isPerc
        ? 'Discount Value (%) <span class="req">*</span>'
        : 'Discount Value (₹) <span class="req">*</span>';
    maxDiscField.style.display = isPerc ? '' : 'none';
}

function updateApplicableUI() {
    const isSpecific = appSelect.value === 'specific_user';
    usersField.style.display = isSpecific ? 'block' : 'none';
    usersField.querySelectorAll('input[type=checkbox]').forEach(cb => cb.disabled = !isSpecific);
}

function updateCount() {
    const n = usersField.querySelectorAll('input[type=checkbox]:checked').length;
    selCount.textContent = n;
}

typeSelect.addEventListener('change', updateTypeUI);
appSelect.addEventListener('change', updateApplicableUI);

document.querySelector('input[name="code"]').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});

document.getElementById('user-check-list').addEventListener('change', updateCount);

document.getElementById('check-all').addEventListener('click', function (e) {
    e.preventDefault();
    usersField.querySelectorAll('.user-check-item:not([style*="display: none"]):not([style*="display:none"]) input[type=checkbox]:not(:disabled)')
        .forEach(cb => cb.checked = true);
    updateCount();
});

document.getElementById('uncheck-all').addEventListener('click', function (e) {
    e.preventDefault();
    usersField.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
    updateCount();
});

document.getElementById('user-search').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    usersField.querySelectorAll('.user-check-item').forEach(function (item) {
        const text = item.querySelector('.uname').textContent.toLowerCase()
                   + ' ' + item.querySelector('.umeta').textContent.toLowerCase();
        item.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
    const visible = usersField.querySelectorAll('.user-check-item:not([style*="display: none"]):not([style*="display:none"])').length;
    let emptyEl = document.getElementById('user-search-empty');
    if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.id = 'user-search-empty';
        emptyEl.style.cssText = 'padding:14px;text-align:center;font-size:13px;color:var(--text-400)';
        emptyEl.textContent = 'No users found.';
        document.getElementById('user-check-list').appendChild(emptyEl);
    }
    emptyEl.style.display = visible === 0 ? '' : 'none';
});

updateTypeUI();
updateApplicableUI();
updateCount();
</script>
@endpush
