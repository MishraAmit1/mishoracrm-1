<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Your booking — {{ $appointment->tenant->name ?? 'Booking' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
<style>
body { background: var(--bg-app,#0b0d12); min-height:100vh; padding:24px 16px; }
.bk-wrap { max-width:480px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:26px; text-align:center; }
.bk-icon { font-size:36px; margin-bottom:10px; }
.bk-svc { font-size:18px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.bk-when { font-size:14px; color:var(--text-300); margin-bottom:16px; }
.bk-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.bk-flash.success { background:#E1F5EE; color:#0F6E56; border:1px solid #9FE1CB; }
.bk-flash.error   { background:#FCEBEB; color:#A32D2D; border:1px solid #F09595; }
.bk-flash.info    { background:#E6F1FB; color:#185FA5; border:1px solid #B5D4F4; }
.bk-badge { display:inline-flex; padding:5px 14px; border-radius:20px; font-size:12.5px; font-weight:600; margin-bottom:14px; }
.bk-badge.booked, .bk-badge.confirmed { background:var(--green-dim); color:var(--green); }
.bk-badge.cancelled, .bk-badge.no_show { background:var(--red-dim); color:var(--red); }
.bk-badge.completed { background:var(--accent-dim); color:var(--accent); }
.bk-btn { padding:11px 20px; border-radius:10px; border:none; background:var(--red-dim); color:var(--red); font-size:13.5px; font-weight:600; cursor:pointer; }
</style>
</head>
<body>

<div class="bk-wrap">
    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        <div class="bk-icon">{{ $appointment->status === 'cancelled' ? '✕' : '✓' }}</div>
        <div class="bk-svc">{{ $appointment->service?->name ?? 'Appointment' }}</div>
        <div class="bk-when">{{ $appointment->starts_at->format('d M Y, h:i A') }} — with {{ $appointment->tenant->name ?? '' }}</div>
        <div>
            <span class="bk-badge {{ $appointment->status }}">{{ \App\Models\Appointment::statuses()[$appointment->status] ?? ucfirst($appointment->status) }}</span>
        </div>

        @if(in_array($appointment->status, ['booked', 'confirmed']))
        <form method="POST" action="{{ route('public.booking.cancel', $appointment->public_token) }}"
              onsubmit="return confirm('Cancel this booking?')">
            @csrf
            <button type="submit" class="bk-btn">Cancel Booking</button>
        </form>
        @endif
    </div>
</div>

</body>
</html>
