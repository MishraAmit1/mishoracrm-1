{{--
Sidebar Component
Usage: @include('components.layout.sidebar')

Active state: add route names to the arrays in isActive() checks.
Badge counts: pass from controller via shared view data.
--}}

@php
    $user = Auth::user();
    $tenant = $user?->tenant;

    // Helper: is any of these routes currently active?
    $isActive = fn(array $routes) => request()->routeIs($routes);
@endphp

<aside class="sidebar" id="sidebar">

    {{-- ── Logo ──────────────────────────────────────────────── --}}
    {{-- <a href="{{ route('dashboard') }}" class="sidebar__logo"> --}}
        <a href="#" class="sidebar__logo"></a>
        <div class="sidebar__logo-icon">
            @if ($tenant?->logo)
                <img src="{{ asset('storage/logos/' . $tenant->logo) }}" alt="{{ $tenant->name }}"
                    style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
            @else
                {{ strtoupper(substr($tenant?->name ?? 'C', 0, 2)) }}
            @endif
        </div>
        <div class="sidebar__logo-text">
            <span class="sidebar__logo-name">{{ $tenant?->name ?? config('app.name') }}</span>
            <span class="sidebar__logo-tagline">CRM Workspace</span>
        </div>
    </a>

    {{-- ── Navigation ──────────────────────────────────────── --}}
    <nav class="sidebar__nav" aria-label="Primary navigation">

        {{-- Main --}}
        <div class="sidebar__group">
            <div class="sidebar__group-label">Main</div>

            {{-- <a href="{{ route('dashboard') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['dashboard']) ? 'is-active' : '' }}" data-tooltip="Dashboard">
                <span class="sidebar__item-icon">
                    <i data-feather="home"></i>
                </span>
                <span class="sidebar__item-label">Dashboard</span>
            </a>
        </div>

        {{-- CRM --}}
        <div class="sidebar__group">
            <div class="sidebar__group-label">CRM</div>

            {{-- <a href="{{ route('crm.leads.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.leads.*']) ? 'is-active' : '' }}" data-tooltip="Leads">
                <span class="sidebar__item-icon">
                    <i data-feather="user-plus"></i>
                </span>
                <span class="sidebar__item-label">Leads</span>
                @if (($leadsCount = view()->shared('sidebar_leads_count', 0)) > 0)
                    <span class="sidebar__item-badge">{{ $leadsCount }}</span>
                @endif
            </a>

            {{-- <a href="{{ route('crm.contacts.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.contacts.*']) ? 'is-active' : '' }}" data-tooltip="Contacts">
                <span class="sidebar__item-icon">
                    <i data-feather="users"></i>
                </span>
                <span class="sidebar__item-label">Contacts</span>
            </a>

            {{-- <a href="{{ route('crm.deals.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.deals.*']) ? 'is-active' : '' }}" data-tooltip="Deals">
                <span class="sidebar__item-icon">
                    <i data-feather="trending-up"></i>
                </span>
                <span class="sidebar__item-label">Deals</span>
            </a>

            {{-- <a href="{{ route('crm.tasks.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.tasks.*']) ? 'is-active' : '' }}" data-tooltip="Tasks">
                <span class="sidebar__item-icon">
                    <i data-feather="check-square"></i>
                </span>
                <span class="sidebar__item-label">Tasks</span>
                @if (($tasksCount = view()->shared('sidebar_tasks_count', 0)) > 0)
                    <span class="sidebar__item-badge">{{ $tasksCount }}</span>
                @endif
            </a>

            {{-- <a href="{{ route('crm.followups.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.followups.*']) ? 'is-active' : '' }}" data-tooltip="Follow-ups">
                <span class="sidebar__item-icon">
                    <i data-feather="phone-call"></i>
                </span>
                <span class="sidebar__item-label">Follow-ups</span>
                @if (($followupsToday = view()->shared('sidebar_followups_today', 0)) > 0)
                    <span class="sidebar__item-badge">{{ $followupsToday }}</span>
                @endif
            </a>
        </div>

        {{-- Billing --}}
        <div class="sidebar__group">
            <div class="sidebar__group-label">Billing</div>

            {{-- <a href="{{ route('crm.quotations.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.quotations.*']) ? 'is-active' : '' }}"
                data-tooltip="Quotations">
                <span class="sidebar__item-icon">
                    <i data-feather="file-text"></i>
                </span>
                <span class="sidebar__item-label">Quotations</span>
            </a>

            {{-- <a href="{{ route('crm.invoices.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['crm.invoices.*']) ? 'is-active' : '' }}" data-tooltip="Invoices">
                <span class="sidebar__item-icon">
                    <i data-feather="credit-card"></i>
                </span>
                <span class="sidebar__item-label">Invoices</span>
            </a>
        </div>

        {{-- HR (only tenant_admin can see) --}}
        @if ($user?->isTenantAdmin() || ($user && $user->getAllPermissions()->contains('name', 'view_hr')))
            <div class="sidebar__group">
                <div class="sidebar__group-label">HR</div>

                {{-- <a href="{{ route('hr.staff.index') }}" --}} <a href="#"
                    class="sidebar__item {{ $isActive(['hr.staff.*']) ? 'is-active' : '' }}" data-tooltip="Staff">
                    <span class="sidebar__item-icon">
                        <i data-feather="briefcase"></i>
                    </span>
                    <span class="sidebar__item-label">Staff</span>
                </a>

                {{-- <a href="{{ route('hr.attendance.index') }}" --}} <a href="#"
                    class="sidebar__item {{ $isActive(['hr.attendance.*']) ? 'is-active' : '' }}" data-tooltip="Attendance">
                    <span class="sidebar__item-icon">
                        <i data-feather="clock"></i>
                    </span>
                    <span class="sidebar__item-label">Attendance</span>
                </a>
            </div>
        @endif

        {{-- Communication --}}
        <div class="sidebar__group">
            <div class="sidebar__group-label">Communication</div>

            {{-- <a href="{{ route('communication.email-templates.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['communication.email-templates.*']) ? 'is-active' : '' }}"
                data-tooltip="Email Templates">
                <span class="sidebar__item-icon">
                    <i data-feather="mail"></i>
                </span>
                <span class="sidebar__item-label">Email Templates</span>
            </a>

            {{-- <a href="{{ route('communication.whatsapp-templates.index') }}" --}} <a href="#"
                class="sidebar__item {{ $isActive(['communication.whatsapp-templates.*']) ? 'is-active' : '' }}"
                data-tooltip="WhatsApp">
                <span class="sidebar__item-icon">
                    <i data-feather="message-circle"></i>
                </span>
                <span class="sidebar__item-label">WhatsApp</span>
            </a>
        </div>

        {{-- Settings (admin only) --}}
        @if ($user?->isTenantAdmin())
            <div class="sidebar__group">
                <div class="sidebar__group-label">Admin</div>

                {{-- <a href="{{ route('settings.company') }}" --}} <a href="#"
                    class="sidebar__item {{ $isActive(['settings.*']) ? 'is-active' : '' }}" data-tooltip="Settings">
                    <span class="sidebar__item-icon">
                        <i data-feather="settings"></i>
                    </span>
                    <span class="sidebar__item-label">Settings</span>
                </a>

                {{-- <a href="{{ route('settings.roles') }}" --}} <a href="#"
                    class="sidebar__item {{ $isActive(['settings.roles*']) ? 'is-active' : '' }}" data-tooltip="Roles">
                    <span class="sidebar__item-icon">
                        <i data-feather="shield"></i>
                    </span>
                    <span class="sidebar__item-label">Roles & Permissions</span>
                </a>
            </div>
        @endif

    </nav>

    {{-- ── User Footer ──────────────────────────────────────── --}}
    <div class="sidebar__footer">

        {{-- Trial banner (if trial active) --}}
        @if ($tenant?->subscription?->status === 'trial')
            @php
                $daysLeft = $tenant->subscription->trial_ends_at
                    ? now()->diffInDays($tenant->subscription->trial_ends_at, false)
                    : 0;
            @endphp
            <div style="
                    background: rgba(37, 99, 235, 0.12);
                    border: 1px solid rgba(37, 99, 235, 0.2);
                    border-radius: var(--radius-md);
                    padding: var(--space-2) var(--space-3);
                    margin-bottom: var(--space-2);
                    overflow: hidden;
                    transition: opacity 0.15s ease;
                " class="{{ $daysLeft > 0 ? '' : '' }}">
                <div style="font-size: var(--text-xs); color: #93C5FD; font-weight: var(--weight-semibold);">
                    Trial: {{ max(0, $daysLeft) }} days left
                </div>
                {{-- <a href="{{ route('settings.subscription') }}" --}} <a href="#"
                    style="font-size: var(--text-xs); color: #60A5FA; text-decoration: underline;">
                    Upgrade now →
                </a>
            </div>
        @endif

        <div class="sidebar__user" id="sidebarUserMenu">
            <div class="sidebar__user-avatar">
                @if ($user?->avatar)
                    <img src="{{ asset('storage/avatars/' . $user->avatar) }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
                @endif
            </div>
            <div class="sidebar__user-info">
                <div class="sidebar__user-name">{{ $user?->name }}</div>
                <div class="sidebar__user-role">{{ ucfirst(str_replace('_', ' ', $user?->user_type ?? '')) }}</div>
            </div>
        </div>
    </div>

</aside>