<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ Auth::user()?->tenant?->name ?? config('app.name') }}</title>

    {{-- Fonts: DM Sans for UI sharpness --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

    {{-- Core CSS --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Feather Icons --}}
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

    {{-- Page-specific head --}}
    @stack('styles')
</head>

<body class="{{ auth()->user()?->isSuperAdmin() ? 'role-superadmin' : 'role-tenant' }}">

    {{-- Mobile sidebar overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    @include('components.layouts.sidebar')

    {{-- Sidebar collapse toggle (desktop) --}}
    <button class="sidebar__toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </button>

    {{-- ── Main Wrapper ────────────────────────────────────────── --}}
    <div class="main-wrapper" id="mainWrapper">

        {{-- Top Navbar --}}
        @include('components.layouts.navbar')

        {{-- Page Content --}}
        <main class="main-content" id="mainContent">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="alert alert--success" style="margin-bottom: var(--space-4);">
                    <span class="alert__icon"><i data-feather="check-circle" width="16" height="16"></i></span>
                    <div class="alert__body">{{ session('success') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert--danger" style="margin-bottom: var(--space-4);">
                    <span class="alert__icon"><i data-feather="alert-circle" width="16" height="16"></i></span>
                    <div class="alert__body">{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger" style="margin-bottom: var(--space-4);">
                    <span class="alert__icon"><i data-feather="alert-triangle" width="16" height="16"></i></span>
                    <div class="alert__body">
                        <div class="alert__title">Please fix the following errors</div>
                        @foreach ($errors->all() as $error)
                            <div class="alert__text">{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Page content injected here --}}
            @yield('content')

        </main>
    </div>

    {{-- ── Toast Container ─────────────────────────────────────── --}}
    <div class="toast-container" id="toastContainer"></div>

    {{-- ── Core JS ──────────────────────────────────────────────── --}}
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        // Init feather icons
        feather.replace({ width: 16, height: 16 });

        // Pass server data to JS
        window.APP = {
            csrfToken: '{{ csrf_token() }}',
            userId: {{ auth()->id() }},
            tenantId: {{ auth()->user()?->tenant_id ?? 'null' }},
            baseUrl: '{{ url('/') }}',
        };
    </script>

    @stack('scripts')
</body>
</html>