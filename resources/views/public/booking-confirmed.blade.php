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
body { background: var(--bg-app); min-height:100vh; padding:24px 16px; }
.bk-wrap { max-width:480px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:26px; text-align:center; }
.bk-icon { font-size:36px; margin-bottom:10px; }
.bk-svc { font-size:18px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.bk-when { font-size:14px; color:var(--text-300); margin-bottom:16px; }
.bk-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }
.bk-badge { display:inline-flex; padding:5px 14px; border-radius:20px; font-size:12.5px; font-weight:600; margin-bottom:14px; }
.bk-badge.booked, .bk-badge.confirmed { background:var(--green-dim); color:var(--green); }
.bk-badge.in_progress { background:var(--accent-dim); color:var(--accent); }
.bk-badge.cancelled, .bk-badge.no_show { background:var(--red-dim); color:var(--red); }
.bk-badge.completed { background:var(--accent-dim); color:var(--accent); }
.bk-btn { padding:11px 20px; border-radius:10px; border:none; background:var(--red-dim); color:var(--red); font-size:13.5px; font-weight:600; cursor:pointer; }
.bk-section { text-align:left; border-top:1px solid var(--border-subtle); margin-top:16px; padding-top:16px; }
.bk-label { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.bk-photo-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.bk-photo-grid img { width:100%; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--border-default); }
.bk-field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; text-align:left; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input { width:100%; padding:10px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.bk-sig-wrap { border:1.5px dashed var(--border-default); border-radius:8px; background:#fff; }
.bk-sig-canvas { width:100%; height:140px; display:block; touch-action:none; cursor:crosshair; border-radius:8px; }
.bk-sig-actions { display:flex; justify-content:flex-end; padding:6px; }
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

        @if($appointment->assignedTo)
        <div style="font-size:13px;color:var(--text-300)">Technician: {{ $appointment->assignedTo->name }}</div>
        @endif

        @if(in_array($appointment->status, ['booked', 'confirmed']))
        <form method="POST" action="{{ route('public.booking.cancel', $appointment->public_token) }}"
              onsubmit="return confirm('Cancel this booking?')">
            @csrf
            <button type="submit" class="bk-btn">Cancel Booking</button>
        </form>
        @endif

        @if($appointment->attachments->isNotEmpty())
        <div class="bk-section">
            <div class="bk-label">Job Photos</div>
            <div class="bk-photo-grid">
                @foreach($appointment->attachments as $att)
                <a href="{{ $att->url }}" target="_blank"><img src="{{ $att->url }}" alt="{{ $att->original_name }}"/></a>
                @endforeach
            </div>
        </div>
        @endif

        @if($appointment->status === 'completed')
        <div class="bk-section">
            <div class="bk-label">Sign-off</div>
            @if($appointment->customer_signed_at)
                <div style="font-size:13.5px;color:var(--text-100)">Signed by <strong>{{ $appointment->customer_signed_name }}</strong> on {{ $appointment->customer_signed_at->format('d M Y, h:i A') }}. Thank you!</div>
            @else
                <div style="font-size:13px;color:var(--text-300);margin-bottom:12px">Please review the completed work and sign below to approve.</div>
                <form method="POST" action="{{ route('public.booking.sign', $appointment->public_token) }}" id="signForm">
                    @csrf
                    <div class="bk-field">
                        <label>Your Full Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="signed_name" class="bk-input" required maxlength="150" placeholder="Type your full name to sign"/>
                    </div>
                    <div class="bk-field">
                        <label>Signature <span style="color:var(--red)">*</span></label>
                        <div class="bk-sig-wrap">
                            <canvas class="bk-sig-canvas" id="sigCanvas"></canvas>
                            <div class="bk-sig-actions">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSignature()">Clear</button>
                            </div>
                        </div>
                        <input type="hidden" name="signature_data" id="signatureData"/>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Approve &amp; Sign</button>
                </form>
            @endif
        </div>
        @endif
    </div>
</div>

@if($appointment->status === 'completed' && !$appointment->customer_signed_at)
<script>
const canvas = document.getElementById('sigCanvas');
const ctx = canvas.getContext('2d');
const ratio = window.devicePixelRatio || 1;

function resize(){
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#1a1a2e';
}
resize();

let drawing = false;
function pos(e){
    const rect = canvas.getBoundingClientRect();
    const t = e.touches ? e.touches[0] : e;
    return { x: t.clientX - rect.left, y: t.clientY - rect.top };
}
function start(e){ drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
function move(e){ if(!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
function end(){ drawing = false; }

canvas.addEventListener('mousedown', start);
canvas.addEventListener('mousemove', move);
window.addEventListener('mouseup', end);
canvas.addEventListener('touchstart', start, {passive:false});
canvas.addEventListener('touchmove', move, {passive:false});
canvas.addEventListener('touchend', end);

function clearSignature(){
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

document.getElementById('signForm').addEventListener('submit', function(e){
    document.getElementById('signatureData').value = canvas.toDataURL('image/png');
});
</script>
@endif

</body>
</html>
