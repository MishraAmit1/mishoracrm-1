<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Book an appointment — {{ $tenant->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
<style>
body { background: var(--bg-app,#0b0d12); min-height:100vh; padding:24px 16px; }
.bk-wrap { max-width:560px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.bk-brand { text-align:center; padding:8px 0 4px; font-size:13px; color:var(--text-300); }
.bk-title { text-align:center; font-size:20px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; padding:22px; }
.bk-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.bk-flash.success { background:#E1F5EE; color:#0F6E56; border:1px solid #9FE1CB; }
.bk-flash.error   { background:#FCEBEB; color:#A32D2D; border:1px solid #F09595; }
.bk-flash.info    { background:#E6F1FB; color:#185FA5; border:1px solid #B5D4F4; }
.bk-field    { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input { width:100%; padding:10px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.bk-svc { display:flex; flex-direction:column; gap:8px; }
.bk-svc-opt { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border:1.5px solid var(--border-default); border-radius:10px; cursor:pointer; transition:border-color .15s; }
.bk-svc-opt:hover, .bk-svc-opt.sel { border-color:var(--accent); background:var(--accent-dim); }
.bk-svc-name { font-size:13.5px; font-weight:600; color:var(--text-100); }
.bk-svc-meta { font-size:12px; color:var(--text-400); }
.bk-slots { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:8px; }
.bk-slot { padding:9px 4px; text-align:center; border:1.5px solid var(--border-default); border-radius:8px; font-size:12.5px; font-family:'DM Mono',monospace; color:var(--text-200); cursor:pointer; }
.bk-slot:hover, .bk-slot.sel { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
.bk-empty { font-size:12.5px; color:var(--text-400); padding:10px 0; }
.bk-btn { width:100%; padding:12px; border-radius:10px; border:none; background:var(--accent); color:#fff; font-size:14px; font-weight:600; cursor:pointer; }
.bk-btn:disabled { opacity:.5; cursor:not-allowed; }
.bk-step { display:none; }
.bk-step.show { display:block; }
.bk-back { font-size:12.5px; color:var(--text-400); cursor:pointer; margin-bottom:10px; display:inline-block; }
.bk-disabled { text-align:center; padding:30px 10px; color:var(--text-300); font-size:13.5px; }
</style>
</head>
<body>

<div class="bk-wrap">
    <div class="bk-brand">Book an appointment with</div>
    <div class="bk-title">{{ $tenant->name }}</div>

    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        @if(!$enabled)
            <div class="bk-disabled">Online booking is currently unavailable. Please contact us directly.</div>
        @elseif($services->isEmpty())
            <div class="bk-disabled">No bookable services available right now.</div>
        @else
            {{-- Step 1: pick a service --}}
            <div class="bk-step show" id="step1">
                <div class="bk-field"><label>Choose a Service</label></div>
                <div class="bk-svc" id="serviceList">
                    @foreach($services as $s)
                    <div class="bk-svc-opt" data-id="{{ $s->id }}" data-name="{{ $s->name }}" onclick="selectService(this)">
                        <div>
                            <div class="bk-svc-name">{{ $s->name }}</div>
                            @if($s->description)<div class="bk-svc-meta">{{ $s->description }}</div>@endif
                        </div>
                        <div class="bk-svc-meta">₹{{ number_format($s->rate, 0) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Step 2: pick a date + slot --}}
            <div class="bk-step" id="step2">
                <span class="bk-back" onclick="goToStep(1)">← Back</span>
                <div class="bk-field">
                    <label>Date</label>
                    <input type="date" id="dateInput" class="bk-input" min="{{ now()->toDateString() }}"
                           max="{{ now()->addDays(60)->toDateString() }}" value="{{ now()->toDateString() }}" onchange="loadSlots()"/>
                </div>
                <div class="bk-field">
                    <label>Available Times</label>
                    <div class="bk-slots" id="slotsContainer"></div>
                </div>
            </div>

            {{-- Step 3: contact details --}}
            <div class="bk-step" id="step3">
                <span class="bk-back" onclick="goToStep(2)">← Back</span>
                <form method="POST" action="{{ route('public.booking.store', $token) }}" id="bookingForm">
                    @csrf
                    <input type="hidden" name="service_id" id="formServiceId"/>
                    <input type="hidden" name="date" id="formDate"/>
                    <input type="hidden" name="time" id="formTime"/>

                    <div class="bk-field">
                        <label>Your Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="bk-input" required/>
                    </div>
                    <div class="bk-field">
                        <label>Phone <span style="color:var(--red)">*</span></label>
                        <input type="tel" name="phone" class="bk-input" required/>
                    </div>
                    <div class="bk-field">
                        <label>Email <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <input type="email" name="email" class="bk-input"/>
                    </div>
                    <div class="bk-field">
                        <label>Notes <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <textarea name="notes" class="bk-input" rows="2"></textarea>
                    </div>

                    <button type="submit" class="bk-btn">Confirm Booking</button>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
let selectedServiceId = null;
let selectedTime = null;

function goToStep(n){
    document.querySelectorAll('.bk-step').forEach(s => s.classList.remove('show'));
    document.getElementById('step' + n).classList.add('show');
}

function selectService(el){
    document.querySelectorAll('.bk-svc-opt').forEach(o => o.classList.remove('sel'));
    el.classList.add('sel');
    selectedServiceId = el.dataset.id;
    document.getElementById('formServiceId').value = selectedServiceId;
    goToStep(2);
    loadSlots();
}

function loadSlots(){
    const date = document.getElementById('dateInput').value;
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '<div class="bk-empty">Loading...</div>';

    fetch(`{{ route('public.booking.slots', $token) }}?service_id=${selectedServiceId}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if(!data.slots || !data.slots.length){
                container.innerHTML = '<div class="bk-empty">No slots available on this date.</div>';
                return;
            }
            data.slots.forEach(t => {
                const div = document.createElement('div');
                div.className = 'bk-slot';
                div.textContent = t;
                div.onclick = () => selectSlot(t, div);
                container.appendChild(div);
            });
        })
        .catch(() => { container.innerHTML = '<div class="bk-empty">Could not load slots.</div>'; });
}

function selectSlot(time, el){
    document.querySelectorAll('.bk-slot').forEach(s => s.classList.remove('sel'));
    el.classList.add('sel');
    selectedTime = time;
    document.getElementById('formDate').value = document.getElementById('dateInput').value;
    document.getElementById('formTime').value = time;
    goToStep(3);
}
</script>

</body>
</html>
