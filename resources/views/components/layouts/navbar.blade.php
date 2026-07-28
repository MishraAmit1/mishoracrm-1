{{--
    Navbar Component
    Usage: @include('components.layout.navbar')
--}}

@php
    $user = auth()->user();
    $breadcrumbs = view()->shared('breadcrumbs', []);
    $notificationsCount = view()->shared('notifications_count', 0);
@endphp

<header class="navbar" id="navbar">

    {{-- Mobile hamburger --}}
    <button class="navbar__hamburger" id="mobileMenuBtn" aria-label="Open menu">
        <i data-feather="menu" width="20" height="20"></i>
    </button>

    {{-- Breadcrumbs --}}
    <nav class="navbar__breadcrumbs" aria-label="Breadcrumb">
        @if (!empty($breadcrumbs))
            @foreach ($breadcrumbs as $crumb)
                <div class="breadcrumb__item {{ $loop->last ? 'is-current' : '' }}">
                    @if (!$loop->last)
                        @if (isset($crumb['url']))
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @else
                            <span>{{ $crumb['label'] }}</span>
                        @endif
                        <span class="breadcrumb__separator">
                            <i data-feather="chevron-right" width="12" height="12"></i>
                        </span>
                    @else
                        <span>{{ $crumb['label'] }}</span>
                    @endif
                </div>
            @endforeach
        @else
            <div class="breadcrumb__item is-current">
                @yield('page_title', 'Dashboard')
            </div>
        @endif
    </nav>

    {{-- Right actions --}}
    <div class="navbar__actions">

        {{-- Global search --}}
        <div class="navbar__search">
            <span class="navbar__search-icon">
                <i data-feather="search" width="14" height="14"></i>
            </span>
            <input
                type="text"
                class="navbar__search-input"
                placeholder="Search leads, contacts…"
                id="globalSearch"
                autocomplete="off"
            >
        </div>

        {{-- Notifications --}}
        <div class="dropdown" id="notificationsDropdown">
            <button class="navbar__icon-btn" id="notificationsBtn" aria-label="Notifications">
                <i data-feather="bell" width="17" height="17"></i>
                @if ($notificationsCount > 0)
                    <span class="badge-dot"></span>
                @endif
            </button>

            <div class="dropdown__menu notifications-panel">
                <div class="notifications-panel__header">
                    <span class="notifications-panel__title">
                        Notifications
                        @if ($notificationsCount > 0)
                            <span class="badge badge--primary" style="margin-left:4px;">{{ $notificationsCount }}</span>
                        @endif
                    </span>
                    <span class="notifications-panel__mark-all">Mark all read</span>
                </div>
                <div class="notifications-panel__list" id="notificationsList">
                    {{-- Populated via AJAX or Livewire --}}
                    <div class="notification-item is-unread">
                        <div class="notification-item__icon" style="background: var(--color-primary-subtle);">🎯</div>
                        <div class="notification-item__content">
                            <div class="notification-item__text">
                                New lead <strong>Rahul Sharma</strong> assigned to you
                            </div>
                            <div class="notification-item__time">5 minutes ago</div>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-item__icon" style="background: var(--color-success-subtle);">✅</div>
                        <div class="notification-item__content">
                            <div class="notification-item__text">
                                Invoice <strong>#INV-2024-041</strong> was paid
                            </div>
                            <div class="notification-item__time">2 hours ago</div>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-item__icon" style="background: var(--color-warning-subtle);">⏰</div>
                        <div class="notification-item__content">
                            <div class="notification-item__text">
                                Follow-up reminder: <strong>Priya Patel</strong> today at 3 PM
                            </div>
                            <div class="notification-item__time">3 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick add --}}
        <div class="dropdown" id="quickAddDropdown">
            <button class="navbar__icon-btn btn btn--primary btn--sm" id="quickAddBtn"
                    aria-label="Quick add"
                    style="width:auto; padding: 0 var(--space-3); gap: var(--space-2); font-size: var(--text-xs); font-weight: var(--weight-semibold);">
                <i data-feather="plus" width="14" height="14"></i>
                New
            </button>
            <div class="dropdown__menu" style="min-width:180px;">
                <div class="dropdown__body">
                    {{-- <a href="{{ route('crm.leads.create') }}" --}}
                <a href="#"
                    class="dropdown__item">
                        <i data-feather="user-plus" width="14" height="14"></i> New Lead
                    </a>
                    {{-- <a href="{{ route('crm.contacts.create') }}"  --}}
                    <a href="#"
                    class="dropdown__item">
                        <i data-feather="user" width="14" height="14"></i> New Contact
                    </a>
                    {{-- <a href="{{ route('crm.deals.create') }}"  --}}
                    <a href="#"
                    class="dropdown__item">
                        <i data-feather="trending-up" width="14" height="14"></i> New Deal
                    </a>
                    <hr class="dropdown__divider">
                    {{-- <a href="{{ route('crm.tasks.create') }}" --}}
                     <a href="#"
                     class="dropdown__item">
                        <i data-feather="check-square" width="14" height="14"></i> New Task
                    </a>
                    {{-- <a href="{{ route('crm.quotations.create') }}" --}}
                     <a href="#"
                     class="dropdown__item">
                        <i data-feather="file-text" width="14" height="14"></i> New Quotation
                    </a>
                </div>
            </div>
        </div>

        {{-- Profile --}}
        <div class="dropdown" id="profileDropdown">
            <div class="navbar__profile" id="profileBtn">
                <div class="navbar__profile-avatar">
                    @if ($user?->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
                    @endif
                </div>
                <span class="navbar__profile-name">{{ $user?->name }}</span>
                <i data-feather="chevron-down" width="12" height="12" style="color: var(--text-muted); flex-shrink:0;"></i>
            </div>

            <div class="dropdown__menu" style="right:0;">
                <div class="dropdown__header">
                    <div class="dropdown__user-name">{{ $user?->name }}</div>
                    <div class="dropdown__user-email">{{ $user?->email }}</div>
                </div>
                <div class="dropdown__body">
                    {{-- <a href="{{ route('profile.edit') }}"  --}}   <a href="#"
                     class="dropdown__item">
                        <i data-feather="user" width="14" height="14"></i> My Profile
                    </a>
                    {{-- <a href="{{ route('settings.company') }}" --}}   <a href="#"
                     class="dropdown__item">
                        <i data-feather="settings" width="14" height="14"></i> Settings
                    </a>
                    @if ($user?->tenant?->subscription)
                        {{-- <a href="{{ route('settings.subscription') }}"  --}}  <a href="#"
                        class="dropdown__item">
                            <i data-feather="credit-card" width="14" height="14"></i>
                            Subscription
                            @if ($user->tenant->subscription->status === 'trial')
                                <span class="badge badge--warning" style="margin-left:auto;">Trial</span>
                            @endif
                        </a>
                    @endif
                    <hr class="dropdown__divider">
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="dropdown__item dropdown__item--danger" style="width:100%;">
                            <i data-feather="log-out" width="14" height="14"></i> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</header>