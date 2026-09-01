@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
    @include('partials.panel-ui')
    <style>
        /* Tenant-dashboard-only tweaks on top of the shared panel system. */
        .bgrid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 12px; margin-top: 12px; }
        @media(max-width:1150px) { .bgrid { grid-template-columns: minmax(0, 1fr); } }
    </style>
@endpush

@php
    $firstName = explode(' ', Auth::user()->name)[0] ?? '';

    $AVC = ['#6378ff', '#a78bfa', '#2dd4a0', '#f8b84e', '#e05252', '#22d3ee', '#f472b6'];
    if (!function_exists('dashAvc')) {
        function dashAvc(string $n, array $c): string { return $c[ord($n[0] ?? 'A') % count($c)]; }
    }
    if (!function_exists('dashIni')) {
        function dashIni(string $n): string {
            $p = explode(' ', trim($n));
            return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
        }
    }

    $fmtMoney = function ($v) {
        $v = (float) $v;
        if ($v >= 10000000) return '₹' . number_format($v / 10000000, 2) . 'Cr';
        if ($v >= 100000)   return '₹' . number_format($v / 100000, 1) . 'L';
        if ($v >= 1000)     return '₹' . number_format($v / 1000, 0) . 'K';
        return '₹' . number_format($v);
    };

    $arrowUp   = 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941';
    $arrowDown = 'M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181';
    $dotsPath  = 'M12 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM6 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM18 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z';

    // Lead status → tag colour, matching the stage bars in the pipeline.
    $statusTone = [
        'new'       => ['var(--accent)', 'var(--accent-dim)'],
        'contacted' => ['var(--amber)',  'var(--amber-dim)'],
        'qualified' => ['var(--purple)', 'var(--purple-dim)'],
        'converted' => ['var(--green)',  'var(--green-dim)'],
        'lost'      => ['var(--red)',    'var(--red-dim)'],
    ];
@endphp

@section('content')

<div class="dsh">

    {{-- ── Panel header ───────────────────────────────────────────── --}}
    <div class="dsh-head">
        <div>
            <div class="dsh-eyebrow">{{ now()->format('l, d M Y') }}</div>
            <div class="dsh-title">Customer Relationship Management</div>
            <div class="dsh-sub">
                Welcome back {{ $firstName }} — track your leads, deals and sales pipeline.
            </div>
        </div>
        <div class="dsh-acts">
            <a href="{{ route('tenant.contacts.import') }}" class="dbtn">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                Import Contacts
            </a>
            <a href="{{ route('tenant.deals.create') }}" class="dbtn dbtn-accent">
                <svg fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Deal
            </a>
        </div>
    </div>

    {{-- ── Alerts ─────────────────────────────────────────────────── --}}
    @php
        $hasAlerts = ($subscriptionAlerts && ($subscriptionAlerts['expiring'] > 0 || $subscriptionAlerts['expired'] > 0))
            || ($ticketAlerts && $ticketAlerts['open'] > 0)
            || ($appointmentAlerts && $appointmentAlerts['today'] > 0)
            || ($timeTrackingAlerts && $timeTrackingAlerts['running'] > 0)
            || ($manufacturingAlerts && ($manufacturingAlerts['low_stock'] > 0 || $manufacturingAlerts['pending_purchase'] > 0));
    @endphp
    @if($hasAlerts)
    <div class="dalerts">
    @endif

    @if($subscriptionAlerts && ($subscriptionAlerts['expiring'] > 0 || $subscriptionAlerts['expired'] > 0))
        <a href="{{ route('tenant.subscriptions.index', ['status' => $subscriptionAlerts['expired'] > 0 ? 'expired' : 'expiring']) }}"
           class="dalert" style="--a:var(--amber);--a-bg:var(--amber-dim)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span>
                @if($subscriptionAlerts['expiring'] > 0)
                    <b>{{ $subscriptionAlerts['expiring'] }}</b> subscription(s) expiring soon
                @endif
                @if($subscriptionAlerts['expiring'] > 0 && $subscriptionAlerts['expired'] > 0) &middot; @endif
                @if($subscriptionAlerts['expired'] > 0)
                    <b>{{ $subscriptionAlerts['expired'] }}</b> already expired
                @endif
                — review →
            </span>
        </a>
    @endif

    @if($ticketAlerts && $ticketAlerts['open'] > 0)
        <a href="{{ route('tenant.tickets.index', ['status' => $ticketAlerts['unassigned'] > 0 ? '' : 'open']) }}" class="dalert" style="--a:var(--amber);--a-bg:var(--amber-dim)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
            </svg>
            <span>
                <b>{{ $ticketAlerts['open'] }}</b> open ticket(s)
                @if($ticketAlerts['unassigned'] > 0) &middot; <b>{{ $ticketAlerts['unassigned'] }}</b> unassigned @endif
                — review →
            </span>
        </a>
    @endif

    @if($appointmentAlerts && $appointmentAlerts['today'] > 0)
        <a href="{{ route('tenant.appointments.index') }}" class="dalert" style="--a:var(--accent);--a-bg:var(--accent-dim)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
            <span><b>{{ $appointmentAlerts['today'] }}</b> appointment(s) today — view →</span>
        </a>
    @endif

    @if($timeTrackingAlerts && $timeTrackingAlerts['running'] > 0)
        <a href="{{ route('tenant.time-entries.index') }}" class="dalert" style="--a:var(--green);--a-bg:var(--green-dim)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span><b>{{ $timeTrackingAlerts['running'] }}</b> timer(s) still running — review →</span>
        </a>
    @endif

    @if($manufacturingAlerts && ($manufacturingAlerts['low_stock'] > 0 || $manufacturingAlerts['pending_purchase'] > 0))
        <a href="{{ $manufacturingAlerts['low_stock'] > 0 ? route('tenant.products.low-stock') : route('tenant.purchase-requests.index') }}"
           class="dalert" style="--a:var(--red);--a-bg:var(--red-dim)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25m-2.25 2.25V6.75m-8.25 0h16.5c.621 0 1.125-.504 1.125-1.125V4.125c0-.621-.504-1.125-1.125-1.125H3.75c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <span>
                @if($manufacturingAlerts['low_stock'] > 0)
                    <b>{{ $manufacturingAlerts['low_stock'] }}</b> product(s) low on stock
                @endif
                @if($manufacturingAlerts['low_stock'] > 0 && $manufacturingAlerts['pending_purchase'] > 0) &middot; @endif
                @if($manufacturingAlerts['pending_purchase'] > 0)
                    <b>{{ $manufacturingAlerts['pending_purchase'] }}</b> purchase request(s) pending
                @endif
                — review →
            </span>
        </a>
    @endif

    @if($hasAlerts)
    </div>
    @endif

    {{-- ── KPI row ────────────────────────────────────────────────── --}}
    @php
        $kpis = [
            [
                'label' => 'Total Leads',
                'value' => number_format($stats['total_leads'] ?? 0),
                'trend' => $stats['leads_trend'] ?? 0,
                'note'  => ($stats['new_leads_today'] ?? 0) . ' new today',
                'spark' => 'sparkLeads',
                'href'  => route('tenant.leads.index'),
                'icon'  => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            ],
            [
                'label' => 'Active Deals',
                'value' => number_format($stats['active_deals'] ?? 0),
                'trend' => $stats['deals_trend'] ?? 0,
                'note'  => $fmtMoney($stats['pipeline_value'] ?? 0) . ' in pipeline',
                'spark' => 'sparkDeals',
                'href'  => route('tenant.deals.index'),
                'icon'  => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            ],
            [
                'label' => 'Won Deals',
                'value' => $fmtMoney($stats['won_value_month'] ?? 0),
                'trend' => $stats['won_trend'] ?? 0,
                'note'  => ($stats['won_this_month'] ?? 0) . ' closed this month',
                'spark' => 'sparkWon',
                'href'  => route('tenant.deals.index', ['stage' => 'won']),
                'icon'  => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Conversion Rate',
                'value' => ($stats['conversion_rate'] ?? 0) . '%',
                'trend' => $stats['conversion_trend'] ?? 0,
                'note'  => ($stats['conversions_this_month'] ?? 0) . ' converted this month',
                'spark' => 'sparkConv',
                'href'  => route('tenant.leads.index', ['status' => 'converted']),
                'icon'  => 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z',
            ],
        ];

        // Each KPI owns a hue. Cool → warm across the row, so the four cards
        // read as one family rather than four unrelated colours. The rgba
        // tints are pre-computed here because canvas and CSS both need them.
        $kpiTones = [
            ['hex' => '#6378ff', 'wash' => 'rgba(99,120,255,0.11)', 'glow' => 'rgba(99,120,255,0.20)', 'edge' => 'rgba(99,120,255,0.45)'],
            ['hex' => '#22d3ee', 'wash' => 'rgba(34,211,238,0.11)', 'glow' => 'rgba(34,211,238,0.20)', 'edge' => 'rgba(34,211,238,0.45)'],
            ['hex' => '#2dd4a0', 'wash' => 'rgba(45,212,160,0.11)', 'glow' => 'rgba(45,212,160,0.20)', 'edge' => 'rgba(45,212,160,0.45)'],
            ['hex' => '#f8b84e', 'wash' => 'rgba(248,184,78,0.12)', 'glow' => 'rgba(248,184,78,0.22)', 'edge' => 'rgba(248,184,78,0.45)'],
        ];
    @endphp

    <div class="kgrid">
        @foreach($kpis as $i => $k)
            @php $tone = $kpiTones[$i % count($kpiTones)]; @endphp
            <div class="kcard" style="--k:{{ $tone['hex'] }};--kw:{{ $tone['wash'] }};--kg:{{ $tone['glow'] }};--kb:{{ $tone['edge'] }}">
                <div class="khead">
                    <span class="kico">
                        <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['icon'] }}" />
                        </svg>
                    </span>
                    <span class="klabel">{{ $k['label'] }}</span>
                    <a href="{{ $k['href'] }}" class="kdots" title="Open {{ $k['label'] }}">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="{{ $dotsPath }}" /></svg>
                    </a>
                </div>
                <div class="kmid">
                    <span class="knum">{{ $k['value'] }}</span>
                    <div class="kspark"><canvas id="{{ $k['spark'] }}" data-color="{{ $tone['hex'] }}"></canvas></div>
                </div>
                <div class="kfoot">
                    <span class="kdelta {{ $k['trend'] >= 0 ? 'up' : 'down' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['trend'] >= 0 ? $arrowUp : $arrowDown }}" />
                        </svg>
                        {{ $k['trend'] >= 0 ? '+' : '' }}{{ $k['trend'] }}%
                    </span>
                    <span class="knote">{{ $k['note'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── Quick actions ──────────────────────────────────────────── --}}
    @php
        // tone => [icon colour, hover wash]; module => null means always shown.
        $chips = [
            ['Add Lead',         route('tenant.leads.create'),       'accent', null,
                'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
            ['New Deal',         route('tenant.deals.create'),       'green',  null,
                'M12 4.5v15m7.5-7.5h-15'],
            ['Create Task',      route('tenant.tasks.create'),       'amber',  null,
                'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Quotation',        route('tenant.quotations.create'),  'purple', null,
                'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
            ['New Service',      route('tenant.services.create'),    'teal',   'service',
                'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L1.5 3l1.5-1.5L7.5 4.5v1.409l4.26 4.26'],
            ['Book Appointment', route('tenant.appointments.create'),'accent', 'appointments',
                'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
            ['Log Time',         route('tenant.time-entries.index'), 'green',  'time_tracking',
                'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['New Ticket',       route('tenant.tickets.create'),     'amber',  'tickets',
                'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
        ];
        $tenantForChips = auth()->user()->tenant;
    @endphp
    <div class="qrow">
        @foreach($chips as [$label, $href, $tone, $module, $icon])
            @continue($module && !$tenantForChips?->hasModuleEnabled($module))
            <a href="{{ $href }}" class="qchip" style="--qc:var(--{{ $tone }});--qcw:var(--{{ $tone }}-dim)">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- ── Sales pipeline + Deal revenue ──────────────────────────── --}}
    <div class="pgrid">

        <div class="dcard">
            <div class="dcard-h">
                <div>
                    <div class="dcard-t">Sales Pipeline</div>
                    <div class="dcard-s">Deals by stage — biggest open deal per column</div>
                </div>
                <div style="display:flex;gap:6px">
                    <a href="{{ route('tenant.deals.create') }}" class="icobtn" title="New deal">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </a>
                    <a href="{{ route('tenant.deals.pipeline') }}" class="icobtn" title="Open pipeline">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                </div>
            </div>
            <div class="dcard-b">
                <div class="pscroll">
                    <div class="prow">
                        @foreach($pipeline as $p)
                            @php
                                // Controller hands back "var(--accent)" etc; the matching
                                // "-dim" token gives the card header its soft wash.
                                $pWash = str_replace(')', '-dim)', $p['color']);
                            @endphp
                            <div class="pcard" style="--ps:{{ $p['color'] }};--psw:{{ $pWash }}">
                                <div class="phead">
                                    <span class="pbar"></span>
                                    <span class="pname">{{ $p['label'] }}</span>
                                    <span class="pcount">{{ $p['value'] }}</span>
                                </div>
                                <div class="pbody">
                                    <div class="pco">{{ $p['top_company'] ?? 'No deal yet' }}</div>
                                    <div class="pval">{{ $p['top_value'] ?? $p['amount'] }}</div>
                                </div>
                                <div class="pfoot">
                                    <div style="min-width:0">
                                        <div class="prep-l">{{ $p['top_rep'] ? 'Sales Rep' : 'Stage total' }}</div>
                                        <div class="prep-n">{{ $p['top_rep'] ?? $p['amount'] }}</div>
                                    </div>
                                    <div class="pspark">
                                        <canvas class="pspark-c" data-spark='@json($p['spark'])' data-color="{{ $p['color'] }}"></canvas>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="dcard">
            <div class="dcard-h">
                <div>
                    <div class="dcard-t">Deal Revenue</div>
                    <div class="dcard-s">Revenue from paid invoices</div>
                </div>
                <a href="{{ route('tenant.invoices.index') }}" class="kdots" title="Open invoices">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="{{ $dotsPath }}" /></svg>
                </a>
            </div>
            <div class="dcard-b">
                <div class="drev"><canvas id="dealRevChart"></canvas></div>
            </div>
        </div>

    </div>

    {{-- ── Recent leads ───────────────────────────────────────────── --}}
    <div class="dcard">
        <div class="dcard-h">
            <div>
                <div class="dcard-t">Recent Leads</div>
                <div class="dcard-s">Latest additions to your pipeline</div>
            </div>
            <div class="rfil">
                <button type="button" class="rpill on" data-f="all">All</button>
                <button type="button" class="rpill" data-f="new">New</button>
                <button type="button" class="rpill" data-f="qualified">Qualified</button>
                <button type="button" class="rpill" data-f="converted">Converted</button>
                <button type="button" class="rpill" data-f="lost">Lost</button>
                <a href="{{ route('tenant.leads.index') }}" class="icobtn" title="View all leads">
                    <svg fill="currentColor" viewBox="0 0 24 24"><path d="{{ $dotsPath }}" /></svg>
                </a>
            </div>
        </div>
        <div style="overflow-x:auto;padding:0 6px 6px">
            <table class="rtable">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" class="rchk" id="rlAll"></th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Deal Value</th>
                        <th>Stage</th>
                        <th>Sales Rep</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody id="rlBody">
                    @forelse($recentLeads as $lead)
                        @php $tone = $statusTone[$lead['status']] ?? ['var(--text-300)', 'var(--bg-elevated)']; @endphp
                        <tr data-status="{{ $lead['status'] }}">
                            <td class="rtd-chk"><input type="checkbox" class="rchk rl-row"></td>
                            <td data-label="Name">
                                <span class="rname">
                                    <span class="rav" style="background:{{ dashAvc($lead['name'], $AVC) }}">{{ dashIni($lead['name']) }}</span>
                                    <span class="rn">{{ $lead['name'] }}</span>
                                </span>
                            </td>
                            <td data-label="Company">{{ $lead['company'] ?? '—' }}</td>
                            <td data-label="Deal Value" style="color:var(--text-100);font-weight:600">
                                {{ ($lead['value'] ?? 0) > 0 ? $fmtMoney($lead['value']) : '—' }}
                            </td>
                            <td data-label="Stage">
                                <span class="rtag" style="color:{{ $tone[0] }};background:{{ $tone[1] }}">
                                    <i></i>{{ ucfirst($lead['status']) }}
                                </span>
                            </td>
                            <td data-label="Sales Rep">
                                @if($lead['assigned'] && $lead['assigned'] !== '—')
                                    <span class="rname">
                                        <span class="rav rav-sm" style="background:{{ dashAvc($lead['assigned'], $AVC) }}">{{ dashIni($lead['assigned']) }}</span>
                                        <span>{{ $lead['assigned'] }}</span>
                                    </span>
                                @else
                                    <span style="color:var(--text-400)">Unassigned</span>
                                @endif
                            </td>
                            <td data-label="Last Activity" style="color:var(--text-300)">{{ $lead['time'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-300)">No leads yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Agenda + lead sources ──────────────────────────────────── --}}
    <div class="bgrid">

        <div class="dcard">
            <div class="dcard-h">
                <div>
                    <div class="dcard-t">Today's Agenda</div>
                    <div class="dcard-s">{{ $stats['tasks_pending'] ?? 0 }} task(s) pending</div>
                </div>
                <a href="{{ route('tenant.calendar.index') }}" class="icobtn" title="Open calendar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>
            <div class="task-list" style="padding-bottom:4px">
                @forelse($todayTasks as $t)
                    <div class="task-item" @if($t['url'] ?? null) onclick="window.location='{{ $t['url'] }}'" style="cursor:pointer" @endif>
                        <div class="task-check {{ $t['done'] ? 'done' : '' }}"></div>
                        <div class="task-prio {{ $t['priority'] }}"></div>
                        <span class="task-text {{ $t['done'] ? 'done' : '' }}">
                            @if(($t['kind'] ?? 'task') === 'followup')<span style="font-size:9.5px;font-weight:700;color:var(--accent);border:1px solid var(--accent);border-radius:4px;padding:0 4px;margin-right:5px;letter-spacing:.3px">FU</span>@endif
                            {{ $t['text'] }}
                        </span>
                        <span class="task-due">{{ $t['due'] }}</span>
                    </div>
                @empty
                    <div style="padding:22px;text-align:center;color:var(--text-300);font-size:13px">Nothing on today's agenda 🎉</div>
                @endforelse
            </div>
        </div>

        <div class="dcard">
            <div class="dcard-h">
                <div class="dcard-t">Lead Sources</div>
            </div>
            <div class="dcard-b">
                @if(!empty($leadSources))
                    <div style="height:180px"><canvas id="sourceChart"></canvas></div>
                @else
                    <div style="padding:22px;text-align:center;color:var(--text-300);font-size:13px">No lead source data yet.</div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Activity feed ──────────────────────────────────────────── --}}
    <div class="dcard" style="margin-top:14px">
        <div class="dcard-h">
            <div>
                <div class="dcard-t">Recent Activity</div>
                <div class="dcard-s">All team activity across the CRM</div>
            </div>
        </div>
        <div class="activity-list" style="padding-bottom:4px">
            @php
                $activities = $activities ?? [];
                $actSvg = [
                    'lead' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
                    'deal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>',
                    'task' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'msg'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
                    'time' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ];
            @endphp
            @forelse($activities as $act)
                <div class="act-item">
                    <div class="act-dot {{ $act['type'] }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            {!! $actSvg[$act['type']] ?? $actSvg['task'] !!}
                        </svg>
                    </div>
                    <div class="act-content">
                        <div class="act-text">{!! $act['text'] !!}</div>
                        <div class="act-time">{{ $act['time'] }}</div>
                    </div>
                </div>
            @empty
                <div style="padding:22px;text-align:center;color:var(--text-300);font-size:13px">No recent activity.</div>
            @endforelse
        </div>
    </div>

</div>{{-- /.dsh --}}

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Outfit', sans-serif";

        /* Resolve any CSS colour (token, var(), hex) to a concrete rgb() string —
           canvas can't read CSS custom properties itself. */
        function resolveColor(color) {
            const probe = document.createElement('span');
            probe.style.color = color;
            document.body.appendChild(probe);
            const rgb = getComputedStyle(probe).color;
            probe.remove();
            return rgb || color;
        }
        function cssVar(name, fallback) {
            const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return v || fallback;
        }
        function toRgba(color, alpha) {
            const m = resolveColor(color).match(/\d+(\.\d+)?/g);
            return m ? `rgba(${m[0]}, ${m[1]}, ${m[2]}, ${alpha})` : color;
        }

        const C_TEXT = resolveColor(cssVar('--text-100', '#0d0f1a'));
        const C_MUTE = resolveColor(cssVar('--text-300', '#7b84a8'));
        const C_SURF = resolveColor(cssVar('--bg-surface', '#ffffff'));
        const C_LINE = toRgba(cssVar('--text-400', '#b0b8d4'), 0.35);
        const C_ACC  = resolveColor(cssVar('--accent', '#6378ff'));
        Chart.defaults.color = C_MUTE;

        /* ── Mini sparkline (KPI cards + pipeline columns) ─────────
           Bars sit on a faint full-height "track" so the chart still
           reads as a chart on months with no activity — otherwise a
           sparse series renders as one lonely sliver. Pipeline columns
           use a soft area line instead.                              */
        function tinySpark(el, data, color, kind) {
            if (!el || !Array.isArray(data) || !data.length) return;
            const nums = data.map(Number);
            const peak = Math.max.apply(null, nums);
            const isLine = kind === 'line';

            const datasets = [];
            if (!isLine) {
                datasets.push({
                    data: nums.map(() => (peak > 0 ? peak : 1)),
                    backgroundColor: toRgba(color, 0.13),
                    borderWidth: 0,
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.62,
                    categoryPercentage: 0.9,
                    grouped: false,
                });
            }
            datasets.push({
                data: nums,
                borderColor: color,
                backgroundColor: isLine ? toRgba(color, 0.16) : toRgba(color, 0.55),
                borderWidth: isLine ? 2 : 0,
                borderRadius: 4,
                borderSkipped: false,
                pointRadius: 0,
                tension: 0.45,
                fill: isLine ? 'start' : false,
                barPercentage: 0.62,
                categoryPercentage: 0.9,
                grouped: false,
            });

            new Chart(el.getContext('2d'), {
                type: isLine ? 'line' : 'bar',
                data: { labels: nums.map((_, i) => i + 1), datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 3, bottom: 1 } },
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: {
                        x: { display: false, grid: { display: false }, stacked: false },
                        y: (isLine && !(peak > 0))
                            ? { display: false, min: -0.6, max: 1 }
                            : { display: false, beginAtZero: true, stacked: false },
                    },
                    animation: { duration: 550 },
                }
            });
        }

        /* Each KPI canvas carries its card's accent in data-color, so the
           sparkline always matches the tint and icon of the card it sits in. */
        const sparkData = @json($spark);
        [
            ['sparkLeads', sparkData.leads],
            ['sparkDeals', sparkData.deals],
            ['sparkWon', sparkData.won],
            ['sparkConv', sparkData.conversion],
        ].forEach(([id, series]) => {
            const cv = document.getElementById(id);
            if (!cv) return;
            tinySpark(cv, (series || []).slice(-5), resolveColor(cv.dataset.color || C_ACC), 'bar');
        });

        document.querySelectorAll('.pspark-c').forEach(cv => {
            let series = [];
            try { series = JSON.parse(cv.dataset.spark || '[]'); } catch (e) {}
            tinySpark(cv, series, resolveColor(cv.dataset.color || C_ACC), 'line');
        });

        /* ── Deal revenue — pale bars, tallest month called out with a
              floating value pill (the reference's "$24,591" bubble). ── */
        const drVals = @json(array_map(fn ($v) => (float) round($v), array_slice($chartData, -6)));
        const drLabels = @json(collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('M'))->values());
        const drMax = Math.max.apply(null, drVals.concat([0]));

        const peakPill = {
            id: 'peakPill',
            afterDatasetsDraw(chart) {
                if (!(drMax > 0)) return;
                const idx = drVals.indexOf(drMax);
                const bar = chart.getDatasetMeta(1).data[idx];
                if (!bar) return;

                const ctx = chart.ctx;
                const text = '₹' + Number(drMax).toLocaleString('en-IN');
                ctx.save();
                ctx.font = '600 11.5px Outfit, sans-serif';
                const w = ctx.measureText(text).width + 18, h = 24;
                const x = Math.min(Math.max(bar.x - w / 2, 2), chart.width - w - 2);
                const y = Math.max(1, bar.y - h - 11);

                ctx.beginPath();
                if (ctx.roundRect) ctx.roundRect(x, y, w, h, 8); else ctx.rect(x, y, w, h);
                ctx.fillStyle = C_SURF;
                ctx.fill();
                ctx.lineWidth = 1;
                ctx.strokeStyle = C_LINE;
                ctx.stroke();

                ctx.fillStyle = C_TEXT;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(text, x + w / 2, y + h / 2 + 0.5);

                ctx.beginPath();
                ctx.arc(bar.x, bar.y, 3.5, 0, Math.PI * 2);
                ctx.fillStyle = C_ACC;
                ctx.fill();
                ctx.restore();
            }
        };

        const drCtx = document.getElementById('dealRevChart').getContext('2d');

        // Indigo → violet vertical wash on the peak bar; the same ramp as the
        // primary buttons, so the accent reads as one deliberate brand colour.
        const drPeakFill = drCtx.createLinearGradient(0, 0, 0, 172);
        drPeakFill.addColorStop(0, '#8b5cf6');
        drPeakFill.addColorStop(1, C_ACC);

        new Chart(drCtx, {
            type: 'bar',
            data: {
                labels: drLabels,
                datasets: [
                    {
                        // Faint full-height track so every month reads as a column,
                        // even the ones with no paid invoices.
                        data: drVals.map(() => (drMax > 0 ? drMax : 1)),
                        backgroundColor: toRgba(C_ACC, 0.10),
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.5,
                        categoryPercentage: 0.85,
                        grouped: false,
                    },
                    {
                        data: drVals,
                        backgroundColor: drVals.map(v => (v === drMax && drMax > 0) ? drPeakFill : toRgba(C_ACC, 0.38)),
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.5,
                        categoryPercentage: 0.85,
                        grouped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 34 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: C_TEXT, titleColor: C_SURF, bodyColor: C_SURF,
                        padding: 9, displayColors: false, cornerRadius: 8,
                        filter: item => item.datasetIndex === 1,
                        callbacks: { label: ctx => '₹' + Number(ctx.raw).toLocaleString('en-IN') }
                    }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11.5 } } },
                    y: {
                        grid: { color: C_LINE, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            font: { size: 10.5 }, maxTicksLimit: 4,
                            callback: v => v >= 100000 ? '₹' + (v / 100000).toFixed(1) + 'L' : '₹' + (v / 1000).toFixed(0) + 'K',
                        }
                    }
                }
            },
            plugins: [peakPill]
        });

        /* ── Recent leads — select all + status filter pills ─────── */
        const rlAll = document.getElementById('rlAll');
        if (rlAll) {
            rlAll.addEventListener('change', () => {
                document.querySelectorAll('#rlBody tr:not([hidden]) .rl-row').forEach(c => { c.checked = rlAll.checked; });
            });
        }

        document.querySelectorAll('.rpill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.rpill').forEach(p => p.classList.remove('on'));
                pill.classList.add('on');
                const f = pill.dataset.f;
                document.querySelectorAll('#rlBody tr').forEach(tr => {
                    tr.hidden = !(f === 'all' || tr.dataset.status === f);
                });
            });
        });

        /* ── Lead sources ───────────────────────────────────────── */
        @if(!empty($leadSources))
        @php
            $sourceLabelsForChart = [];
            foreach (array_keys($leadSources) as $sourceKey) {
                $sourceLabelsForChart[] = ucfirst(str_replace('_', ' ', $sourceKey));
            }
            $sourceCountsForChart = array_values($leadSources);
        @endphp
        new Chart(document.getElementById('sourceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($sourceLabelsForChart),
                datasets: [{
                    data: @json($sourceCountsForChart),
                    backgroundColor: ['#6378ff', '#2dd4a0', '#a78bfa', '#f8b84e', '#ff5257', '#22d3ee', '#f472b6', '#9ca3af'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: C_MUTE, font: { size: 11.5 }, padding: 10, boxWidth: 8, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: C_TEXT, titleColor: C_SURF, bodyColor: C_SURF,
                        padding: 9, displayColors: false, cornerRadius: 8,
                    }
                },
            }
        });
        @endif
    </script>
@endpush
