@extends('layouts.app')
@section('title', 'Settings')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.settings-layout {
    display:grid;
    grid-template-columns:240px 1fr;
    gap:16px;
    align-items:start;
}
@media(max-width:900px) { .settings-layout { grid-template-columns:1fr; } }

/* ── Sidebar tabs ────────────────────────────────────────────────── */
.settings-nav {
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:var(--r-lg);
    overflow:hidden;
    position:sticky;
    top:80px;
}
.nav-head {
    padding:14px 16px;
    border-bottom:1px solid var(--border-subtle);
    font-size:11.5px; font-weight:700;
    color:var(--text-300);
    text-transform:uppercase; letter-spacing:.5px;
}
.nav-item {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px;
    font-size:13.5px; font-weight:500;
    color:var(--text-200);
    cursor:pointer;
    border-bottom:1px solid var(--border-subtle);
    transition:all .15s var(--ease);
    border-left:3px solid transparent;
    user-select:none;
}
.nav-item:last-child { border-bottom:none; }
.nav-item svg { width:16px; height:16px; flex-shrink:0; }
.nav-item:hover { background:var(--bg-elevated); color:var(--text-100); }
.nav-item.active {
    background:var(--accent-dim);
    color:var(--accent);
    border-left-color:var(--accent);
    font-weight:600;
}

/* ── Form card ───────────────────────────────────────────────────── */
.settings-card {
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:var(--r-lg);
    overflow:hidden;
}
.settings-tab { display:none; }
.settings-tab.active { display:block; }

.card-head {
    padding:20px 24px;
    border-bottom:1px solid var(--border-subtle);
    display:flex; align-items:center; justify-content:space-between;
}
.card-title { font-size:15px; font-weight:700; color:var(--text-100); }
.card-sub   { font-size:13px; color:var(--text-300); margin-top:2px; }
.card-body  { padding:24px; }
.card-footer {
    padding:16px 24px;
    background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
    display:flex; justify-content:flex-end; gap:10px;
}

/* ── Form fields ─────────────────────────────────────────────────── */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.span-2 { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .span-2 { grid-column:1; } }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px;
    background:var(--bg-input);
    border:1.5px solid var(--border-default);
    border-radius:var(--r-sm);
    color:var(--text-100);
    font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-textarea { resize:vertical; min-height:80px; }
.field-error { font-size:12px; color:var(--red); font-weight:500; }
.field-hint  { font-size:12px; color:var(--text-400); }

/* ── Avatar ──────────────────────────────────────────────────────── */
.avatar-section {
    display:flex; align-items:center; gap:20px;
    padding:20px 24px;
    border-bottom:1px solid var(--border-subtle);
}
.avatar-img {
    width:80px; height:80px; border-radius:50%;
    object-fit:cover;
    border:3px solid var(--border-default);
}
.avatar-placeholder {
    width:80px; height:80px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:28px; font-weight:800;
    border:3px solid var(--border-default);
    flex-shrink:0;
}
.avatar-info { flex:1; }
.avatar-name { font-size:17px; font-weight:800; color:var(--text-100); margin-bottom:3px; }
.avatar-role { font-size:13px; color:var(--text-300); margin-bottom:10px; }
.avatar-upload {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 14px;
    border:1.5px solid var(--border-default);
    border-radius:var(--r-sm);
    font-size:12.5px; font-weight:600;
    color:var(--text-200); cursor:pointer;
    transition:all .15s; background:none;
    font-family:var(--font);
}
.avatar-upload:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.avatar-upload svg { width:14px; height:14px; }
.avatar-upload.is-uploading { opacity:.6; pointer-events:none; }
.avatar-error { font-size:12px; color:var(--red); font-weight:500; margin-top:6px; }

/* ── Password strength ───────────────────────────────────────────── */
.pw-wrap { position:relative; }
.pw-wrap .field-input { padding-right:42px; }
.pw-eye {
    position:absolute; right:12px; top:50%;
    transform:translateY(-50%);
    background:none; border:none;
    cursor:pointer; color:var(--text-300);
    padding:0; display:flex;
}
.pw-eye svg { width:16px; height:16px; }

.pw-strength { height:4px; border-radius:2px; margin-top:6px; background:var(--border-subtle); overflow:hidden; }
.pw-strength-fill { height:100%; border-radius:2px; transition:width .3s, background .3s; width:0; }
.pw-strength-label { font-size:11px; color:var(--text-400); margin-top:3px; }

/* ── Alert ───────────────────────────────────────────────────────── */
.alert {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px; border-radius:var(--r-sm);
    font-size:13px; font-weight:500;
    margin-bottom:20px;
}
.alert-success { background:var(--green-dim); color:var(--green); border:1px solid rgba(45,212,160,.25); }
.alert-error   { background:var(--red-dim);   color:var(--red);   border:1px solid rgba(255,82,87,.25); }
.alert svg { width:16px; height:16px; flex-shrink:0; }

/* ── Danger zone ─────────────────────────────────────────────────── */
.danger-zone {
    border:1.5px solid rgba(255,82,87,.25);
    border-radius:var(--r-md);
    overflow:hidden;
    margin-top:24px;
}
.danger-head {
    padding:12px 16px;
    background:var(--red-dim);
    font-size:12.5px; font-weight:700;
    color:var(--red);
    text-transform:uppercase; letter-spacing:.4px;
}
.danger-body { padding:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
.danger-desc { font-size:13px; color:var(--text-200); }
.danger-desc strong { color:var(--text-100); }
</style>
@endpush

@section('content')

@php
    $activeTab = session('active_tab', 'profile');
    $avatarColors = [
        ['var(--accent-dim)','var(--accent)'],['var(--green-dim)','var(--green)'],
        ['var(--amber-dim)','var(--amber)'],['var(--purple-dim)','var(--purple)'],
    ];
    [$avBg, $avTx] = $avatarColors[abs(crc32($user->name ?? '')) % 4];
    $userRole = $user->roles->first()?->name ?? 'staff';
    $states   = config('crm.states', []);
@endphp

<div class="page-head">
    <div class="page-title">Settings</div>
</div>

{{-- Success / Error alerts --}}
@if(session('success'))
<div class="alert alert-success">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="alert alert-error">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
    </svg>
    {{ $errors->first() }}
</div>
@endif

<div class="settings-layout">

    {{-- ── Sidebar nav ─────────────────────────────────────────── --}}
    <div class="settings-nav">
        <div class="nav-head">Settings</div>

        <div class="nav-item {{ $activeTab === 'profile' ? 'active':'' }}"
             onclick="switchTab('profile')">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
            My Profile
        </div>

        <div class="nav-item {{ $activeTab === 'password' ? 'active':'' }}"
             onclick="switchTab('password')">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            Change Password
        </div>

        @if($user->isTenantAdmin())
        <div class="nav-item {{ $activeTab === 'company' ? 'active':'' }}"
             onclick="switchTab('company')">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
            </svg>
            Company Details
        </div>
        @endif

        <div class="nav-item {{ $activeTab === 'notifications' ? 'active':'' }}"
             onclick="window.location='{{ route('tenant.notifications.preferences') }}'">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            Notifications
        </div>
        <div class="nav-item {{ $activeTab === 'roles' ? 'active':'' }}"
             onclick="window.location='{{ route('tenant.roles.index') }}'">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            Roles
        </div>
    </div>

    {{-- ── Right panel ─────────────────────────────────────────── --}}
    <div>

        {{-- ══ PROFILE TAB ══════════════════════════════════════ --}}
        <div class="settings-tab {{ $activeTab === 'profile' ? 'active':'' }}"
             id="tab-profile">
            <div class="settings-card">

                {{-- Avatar section --}}
                <div class="avatar-section">
                    @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         alt="{{ $user->name }}" class="avatar-img"/>
                    @else
                    <div class="avatar-placeholder" style="background:{{ $avBg }};color:{{ $avTx }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    @endif
                    <div class="avatar-info">
                        <div class="avatar-name">{{ $user->name }}</div>
                        <div class="avatar-role">
                            {{ ucfirst(str_replace('_',' ', $userRole)) }}
                            @if($user->tenant)
                            · {{ $user->tenant->name }}
                            @endif
                        </div>
                        {{-- Avatar upload --}}
                        <form method="POST" action="{{ route('tenant.settings.avatar') }}"
                              enctype="multipart/form-data" id="avatarForm">
                            @csrf
                            <label class="avatar-upload" id="avatarUploadLabel">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                <span id="avatarUploadLabelText">Change Photo</span>
                                <input type="file" name="avatar" accept="image/*"
                                       style="display:none" id="avatarInput"/>
                            </label>
                        </form>
                        @error('avatar')
                            <div class="avatar-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <script>
                    document.getElementById('avatarInput')?.addEventListener('change', function () {
                        const file = this.files[0];
                        if (!file) return;

                        const maxBytes = 2 * 1024 * 1024; // must match SettingsController@uploadAvatar 'max:2048'
                        if (file.size > maxBytes) {
                            alert('Image is too large. Please choose a file under 2 MB.');
                            this.value = '';
                            return;
                        }

                        const label = document.getElementById('avatarUploadLabel');
                        const labelText = document.getElementById('avatarUploadLabelText');
                        label.classList.add('is-uploading');
                        labelText.textContent = 'Uploading...';

                        this.form.submit();
                    });
                </script>

                {{-- Profile form --}}
                <form method="POST" action="{{ route('tenant.settings.profile') }}">
                    @csrf @method('PUT')

                    <div class="card-body">
                        <div class="form-grid">
                            <div class="field">
                                <label class="field-label">Full Name <span class="req">*</span></label>
                                <input type="text" name="name"
                                       class="field-input {{ $errors->has('name') ? 'is-error':'' }}"
                                       value="{{ old('name', $user->name) }}" required/>
                                @error('name') <span class="field-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="field-label">Email <span class="req">*</span></label>
                                <input type="email" name="email"
                                       class="field-input {{ $errors->has('email') ? 'is-error':'' }}"
                                       value="{{ old('email', $user->email) }}" required/>
                                @error('email') <span class="field-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="field-label">Phone</label>
                                <input type="tel" name="phone" class="field-input"
                                       placeholder="+91 98765 43210"
                                       value="{{ old('phone', $user->phone) }}"/>
                            </div>

                            <div class="field">
                                <label class="field-label">Designation</label>
                                <input type="text" name="designation" class="field-input"
                                       placeholder="e.g. Sales Manager"
                                       value="{{ old('designation', $user->designation) }}"/>
                            </div>
                        </div>

                        {{-- Read-only info --}}
                        <div style="margin-top:20px;padding:14px;background:var(--bg-elevated);border-radius:var(--r-sm);border:1px solid var(--border-subtle)">
                            <div style="font-size:11.5px;font-weight:700;color:var(--text-300);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Account Info</div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                <div>
                                    <div style="font-size:11px;color:var(--text-400)">Role</div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">
                                        {{ ucfirst(str_replace('_',' ', $userRole)) }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--text-400)">Member Since</div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">
                                        {{ $user->created_at->format('d M Y') }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--text-400)">Last Login</div>
                                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--text-400)">Status</div>
                                    <div>
                                        <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:20px;background:{{ $user->is_active ? 'var(--green-dim)':'var(--red-dim)' }};color:{{ $user->is_active ? 'var(--green)':'var(--red)' }}">
                                            {{ $user->is_active ? 'Active':'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══ PASSWORD TAB ══════════════════════════════════════ --}}
        <div class="settings-tab {{ $activeTab === 'password' ? 'active':'' }}"
             id="tab-password">
            <div class="settings-card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Change Password</div>
                        <div class="card-sub">Keep your account secure with a strong password</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('tenant.settings.password') }}">
                    @csrf @method('PUT')

                    <div class="card-body">
                        <div style="max-width:480px;display:flex;flex-direction:column;gap:18px">

                            <div class="field">
                                <label class="field-label">Current Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="current_password" id="curPw"
                                           class="field-input {{ $errors->has('current_password') ? 'is-error':'' }}"
                                           placeholder="Enter current password" required/>
                                    <button type="button" class="pw-eye" onclick="togglePw('curPw')">
                                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('current_password')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label class="field-label">New Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password" id="newPw"
                                           class="field-input"
                                           placeholder="Min. 8 characters"
                                           oninput="checkStrength(this.value)" required/>
                                    <button type="button" class="pw-eye" onclick="togglePw('newPw')">
                                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="pw-strength">
                                    <div class="pw-strength-fill" id="pwFill"></div>
                                </div>
                                <div class="pw-strength-label" id="pwLabel"></div>
                            </div>

                            <div class="field">
                                <label class="field-label">Confirm New Password <span class="req">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_confirmation" id="confPw"
                                           class="field-input"
                                           placeholder="Repeat new password" required/>
                                    <button type="button" class="pw-eye" onclick="togglePw('confPw')">
                                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Tips --}}
                            <div style="padding:14px;background:var(--bg-elevated);border-radius:var(--r-sm);border:1px solid var(--border-subtle)">
                                <div style="font-size:12px;color:var(--text-300);font-weight:700;margin-bottom:8px">Password Tips</div>
                                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:5px">
                                    @foreach(['At least 8 characters', 'Include uppercase & lowercase', 'Include numbers & symbols'] as $tip)
                                    <li style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-300)">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;color:var(--green)">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                        {{ $tip }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══ COMPANY TAB ══════════════════════════════════════ --}}
        @if($user->isTenantAdmin())
        <div class="settings-tab {{ $activeTab === 'company' ? 'active':'' }}"
             id="tab-company">
            <div class="settings-card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Company Details</div>
                        <div class="card-sub">Shown on quotations, invoices & PDFs</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('tenant.settings.company') }}">
                    @csrf @method('PUT')

                    <div class="card-body">
                        <div class="form-grid">

                            <div class="field span-2">
                                <label class="field-label">Company Name <span class="req">*</span></label>
                                <input type="text" name="name" class="field-input"
                                       value="{{ old('name', $tenant->name) }}" required/>
                            </div>

                            <div class="field">
                                <label class="field-label">Company Email</label>
                                <input type="email" name="email" class="field-input"
                                       placeholder="company@email.com"
                                       value="{{ old('email', $tenant->email) }}"/>
                            </div>

                            <div class="field">
                                <label class="field-label">Company Phone</label>
                                <input type="tel" name="phone" class="field-input"
                                       placeholder="+91 98765 43210"
                                       value="{{ old('phone', $tenant->phone) }}"/>
                            </div>

                            <div class="field">
                                <label class="field-label">Website</label>
                                <input type="url" name="website" class="field-input"
                                       placeholder="https://yourcompany.com"
                                       value="{{ old('website', $tenant->settings['website'] ?? '') }}"/>
                            </div>

                            <div class="field">
                                <label class="field-label">GST Number</label>
                                <input type="text" name="gst" class="field-input"
                                       placeholder="22AAAAA0000A1Z5"
                                       value="{{ old('gst', $tenant->settings['gst'] ?? '') }}"/>
                                <span class="field-hint">Used on quotations & invoices</span>
                            </div>

                            <div class="field span-2">
                                <label class="field-label">Address</label>
                                <textarea name="address" class="field-input field-textarea"
                                          placeholder="Street address, building, area...">{{ old('address', $tenant->settings['address'] ?? '') }}</textarea>
                            </div>

                            <div class="field">
                                <label class="field-label">City</label>
                                <input type="text" name="city" class="field-input"
                                       placeholder="Mumbai"
                                       value="{{ old('city', $tenant->settings['city'] ?? '') }}"/>
                            </div>

                            <div class="field">
                                <label class="field-label">State</label>
                                <select name="state" class="field-input field-select">
                                    <option value="">Select State</option>
                                    @foreach($states as $code => $name)
                                    <option value="{{ $code }}"
                                        {{ old('state', $tenant->settings['state'] ?? '') === $code ? 'selected':'' }}>
                                        {{ $name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label class="field-label">Pincode</label>
                                <input type="text" name="pincode" class="field-input"
                                       placeholder="400001"
                                       value="{{ old('pincode', $tenant->settings['pincode'] ?? '') }}"/>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            Save Company Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));

    const tabEl = document.getElementById('tab-' + tab);
    if (tabEl) tabEl.classList.add('active');

    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.getAttribute('onclick')?.includes(tab)) {
            item.classList.add('active');
        }
    });
}

// ── Password toggle ───────────────────────────────────────────────
function togglePw(id) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
}

// ── Password strength ─────────────────────────────────────────────
function checkStrength(val) {
    let score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const fill  = document.getElementById('pwFill');
    const label = document.getElementById('pwLabel');
    const levels = [
        { w:'0%',   bg:'',                    text:'' },
        { w:'25%',  bg:'var(--red)',           text:'Weak' },
        { w:'50%',  bg:'var(--amber)',         text:'Fair' },
        { w:'75%',  bg:'#60a5fa',             text:'Good' },
        { w:'100%', bg:'var(--green)',         text:'Strong ✓' },
    ];
    const level = levels[score] || levels[0];
    fill.style.width      = level.w;
    fill.style.background = level.bg;
    label.textContent     = level.text;
    label.style.color     = level.bg;
}

// ── Active tab on session ─────────────────────────────────────────
const serverTab = '{{ $activeTab }}';
if (serverTab && document.getElementById('tab-' + serverTab)) {
    // already handled by PHP class
}
</script>
@endpush