@extends('layouts.app')
@section('title', 'Schedule Follow-up')

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:720px; }
.form-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.form-section-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:0.4px; margin-bottom:4px; }
.form-section-sub { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-grid .span-2 { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .form-grid .span-2 { grid-column:1; } }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:0.3px; }
.field-label .req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:14px; outline:none;
    transition:border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; appearance:none; cursor:pointer; }
.field-textarea { resize:vertical; min-height:90px; }
.field-error { font-size:12px; color:var(--red); font-weight:500; }

/* Type cards */
.type-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
@media(max-width:600px) { .type-grid { grid-template-columns:repeat(3,1fr); } }
.type-card {
    display:flex; flex-direction:column; align-items:center; gap:6px;
    padding:12px 8px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    cursor:pointer; transition:all 0.15s var(--ease);
    background:none;
}
.type-card:hover { border-color:var(--border-strong); background:var(--bg-hover); }
.type-card.selected { border-color:var(--accent); background:var(--accent-dim); }
.type-card input { display:none; }
.type-icon { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; }
.type-icon svg { width:16px; height:16px; }
.type-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-align:center; }
.type-card.selected .type-label { color:var(--accent); }

.form-actions {
    display:flex; align-items:center; justify-content:flex-end; gap:10px;
    padding:20px 24px; background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
}

/* Lead/contact info box */
.linked-box {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px;
    background:var(--accent-dim);
    border:1.5px solid rgba(99,120,255,0.2);
    border-radius:var(--r-sm);
    margin-bottom:16px;
}
.linked-box svg { width:18px; height:18px; color:var(--accent); flex-shrink:0; }
.linked-name { font-size:13.5px; font-weight:600; color:var(--text-100); }
.linked-sub  { font-size:12px; color:var(--text-300); margin-top:1px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            @if($lead)
                <a href="{{ route('tenant.leads.show', $lead) }}" style="color:var(--text-300);text-decoration:none">{{ $lead->name }}</a>
            @elseif($contact)
                {{-- <a href="{{ route('tenant.contacts.show', $contact) }}" style="color:var(--text-300);text-decoration:none">{{ $contact->name }}</a> --}}
                  <a href="#" style="color:var(--text-300);text-decoration:none">{{ $contact->name }}</a>
            @else
                <a href="{{ route('tenant.followups.index') }}" style="color:var(--text-300);text-decoration:none">Follow-ups</a>
            @endif
            <span style="margin:0 6px">›</span>
            <span>Schedule Follow-up</span>
        </div>
        <div class="page-title">Schedule Follow-up</div>
    </div>
    <a href="{{ $lead ? route('tenant.leads.show',$lead) : ($contact ? route('tenant.contacts.show',$contact) : route('tenant.followups.index')) }}"
       class="btn btn-secondary">← Back</a>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('tenant.followups.store') }}" novalidate>
        @csrf

        {{-- Hidden fields --}}
        @if($lead)
            <input type="hidden" name="lead_id" value="{{ $lead->id }}"/>
        @endif
        @if($contact)
            <input type="hidden" name="contact_id" value="{{ $contact->id }}"/>
        @endif

        {{-- Linked lead/contact info --}}
        @if($lead)
        <div class="form-section" style="padding-bottom:16px">
            <div class="linked-box">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <div>
                    <div class="linked-name">{{ $lead->name }}</div>
                    <div class="linked-sub">Lead · {{ $lead->phone }}</div>
                </div>
            </div>
        </div>
        @elseif($contact)
        <div class="form-section" style="padding-bottom:16px">
            <div class="linked-box">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <div>
                    <div class="linked-name">{{ $contact->name }}</div>
                    <div class="linked-sub">Contact · {{ $contact->phone }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Follow-up type --}}
        <div class="form-section">
            <div class="form-section-title">Follow-up Type <span style="color:var(--red)">*</span></div>
            <div class="form-section-sub">Kaunsa type ka follow-up karna hai</div>

            <div class="type-grid">
                @php
                    $typeIcons = [
                        'call'      => ['icon'=>'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'color'=>'var(--green)',   'bg'=>'var(--green-dim)',  'label'=>'Call'],
                        'email'     => ['icon'=>'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'color'=>'var(--accent)',  'bg'=>'var(--accent-dim)', 'label'=>'Email'],
                        'whatsapp'  => ['icon'=>'M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z', 'color'=>'var(--green)',   'bg'=>'var(--green-dim)',  'label'=>'WhatsApp'],
                        'meeting'   => ['icon'=>'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'color'=>'var(--purple)',  'bg'=>'var(--purple-dim)', 'label'=>'Meeting'],
                        'other'     => ['icon'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'var(--amber)', 'bg'=>'var(--amber-dim)', 'label'=>'Other'],
                    ];
                @endphp

                @foreach($typeIcons as $val => $t)
                <label class="type-card {{ old('type','call') === $val ? 'selected':'' }}" id="tc-{{ $val }}" onclick="selectType('{{ $val }}')">
                    <input type="radio" name="type" value="{{ $val }}" {{ old('type','call') === $val ? 'checked':'' }}/>
                    <div class="type-icon" style="background:{{ $t['bg'] }};color:{{ $t['color'] }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="type-label">{{ $t['label'] }}</span>
                </label>
                @endforeach
            </div>
            @error('type') <p style="font-size:12px;color:var(--red);margin-top:8px">{{ $message }}</p> @enderror
        </div>

        {{-- Schedule details --}}
        <div class="form-section">
            <div class="form-section-title">Schedule Details</div>
            <div class="form-section-sub">Date, time aur assignment</div>

            <div class="form-grid">
                <div class="field">
                    <label class="field-label">Date & Time <span class="req">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           class="field-input {{ $errors->has('scheduled_at') ? 'is-error':'' }}"
                           value="{{ old('scheduled_at', now()->addHour()->format('Y-m-d\TH:i')) }}"
                           required/>
                    @error('scheduled_at') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="field-label">Assign To <span class="req">*</span></label>
                    <select name="assigned_to" class="field-input field-select {{ $errors->has('assigned_to') ? 'is-error':'' }}" required>
                        <option value="">Select staff</option>
                        @foreach($staffList as $staff)
                        <option value="{{ $staff->id }}"
                            {{ old('assigned_to', auth()->id()) == $staff->id ? 'selected':'' }}>
                            {{ $staff->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('assigned_to') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                {{-- Show lead/contact dropdown only if not pre-selected --}}
                @if(!$lead && !$contact)
                <div class="field">
                    <label class="field-label">Link to Lead</label>
                    <select name="lead_id" class="field-input field-select">
                        <option value="">Select lead (optional)</option>
                        @foreach($leads as $l)
                        <option value="{{ $l->id }}" {{ old('lead_id') == $l->id ? 'selected':'' }}>
                            {{ $l->name }} — {{ $l->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="field-label">Link to Contact</label>
                    <select name="contact_id" class="field-input field-select">
                        <option value="">Select contact (optional)</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}" {{ old('contact_id') == $c->id ? 'selected':'' }}>
                            {{ $c->name }} — {{ $c->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        {{-- Notes --}}
        <div class="form-section">
            <div class="form-section-title">Notes</div>
            <div class="form-section-sub">Follow-up ke baare mein kuch notes</div>
            <div class="field">
                <label class="field-label">Notes</label>
                <textarea name="notes" class="field-input field-textarea"
                          placeholder="What to discuss in this follow-up...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ $lead ? route('tenant.leads.show',$lead) : ($contact ? route('tenant.contacts.show',$contact) : route('tenant.followups.index')) }}"
               class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
                Schedule Follow-up
            </button>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
function selectType(val) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('tc-' + val).classList.add('selected');
    document.querySelector(`input[name="type"][value="${val}"]`).checked = true;
}
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '⏳ Scheduling...';
    btn.disabled = true;
});
</script>
@endpush