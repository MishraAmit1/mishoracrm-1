@extends('layouts.app')
@section('title', 'Tickets')

@push('styles')
<style>
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); cursor:pointer; }
.mono { font-family:var(--mono); }
.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-open, .badge-in_progress { background:var(--accent-dim); color:var(--accent); }
.badge-resolved { background:var(--green-dim); color:var(--green); }
.badge-closed { background:var(--bg-elevated); color:var(--text-400); }
.badge-priority { display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
.badge-low { background:var(--bg-elevated); color:var(--text-400); }
.badge-medium { background:var(--accent-dim); color:var(--accent); }
.badge-high { background:#FFF4E5; color:#B36B00; }
.badge-urgent { background:var(--red-dim); color:var(--red); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Tickets</div>
        <div class="page-sub">Customer support requests</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.tickets.create') }}" class="btn btn-primary">+ New Ticket</a>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ session('error') }}
</div>
@endif

<div style="padding:10px 14px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-sm);margin-bottom:16px;font-size:12.5px;color:var(--text-300)">
    Public support link: <code style="color:var(--accent)">{{ $tenant->supportPublicUrl() }}</code>
</div>

{{-- Ticket-confirmation channel + template preferences (tenant-controlled) --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px 16px;margin-bottom:16px">
    <div style="font-size:13px;font-weight:700;color:var(--text-100);margin-bottom:2px">Ticket Confirmation Message</div>
    <div style="font-size:11.5px;color:var(--text-400);margin-bottom:10px">Sent automatically the moment a new ticket is created (public form or manual). Choose which channel(s) it goes out on and customize the wording.</div>
    <form method="POST" action="{{ route('tenant.tickets.preferences') }}">
        @csrf
        <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--text-200);cursor:pointer">
                <input type="checkbox" name="email" value="1" {{ $confirmPrefs['email'] ? 'checked' : '' }} style="width:15px;height:15px;cursor:pointer"/>
                Email the customer
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;{{ $whatsappConnected ? 'color:var(--text-200)' : 'color:var(--text-400)' }};cursor:{{ $whatsappConnected ? 'pointer' : 'not-allowed' }}">
                <input type="checkbox" name="whatsapp" value="1" {{ $confirmPrefs['whatsapp'] ? 'checked' : '' }} {{ $whatsappConnected ? '' : 'disabled' }} style="width:15px;height:15px;cursor:pointer"/>
                WhatsApp the customer
                @unless($whatsappConnected)
                <a href="{{ route('tenant.whatsapp.send') }}" style="color:var(--accent);text-decoration:none;font-size:11.5px">(Connect WhatsApp first)</a>
                @endunless
            </label>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('confirmTemplates').classList.toggle('show')">Customize message</button>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </div>

        <div id="confirmTemplates" style="display:none;flex-direction:column;gap:12px;padding-top:12px;border-top:1px solid var(--border-subtle)">
            <div style="font-size:11px;color:var(--text-400)">Placeholders: <code>@{{contact_name}}</code> <code>@{{ticket_number}}</code> <code>@{{subject}}</code> <code>@{{tenant_name}}</code> <code>@{{tracking_link}}</code> — preview below uses sample data.</div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">Email Subject</label>
                <input type="text" name="email_subject" id="tkEmailSubjectInput" value="{{ $confirmPrefs['email_subject'] }}" oninput="tkUpdatePreview()"
                       style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px"/>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">Email Body <span style="font-weight:400;text-transform:none;color:var(--text-400)">(basic HTML allowed, e.g. &lt;br&gt;)</span></label>
                <textarea name="email_body" id="tkEmailBodyInput" rows="5" oninput="tkUpdatePreview()"
                          style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px;resize:vertical;font-family:var(--mono,monospace)">{{ $confirmPrefs['email_body'] }}</textarea>
                <div style="font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">Preview</div>
                <div style="padding:10px 12px;background:var(--bg-elevated);border:1px dashed var(--border-default);border-radius:var(--r-sm);font-size:13px;color:var(--text-200)">
                    <div style="font-weight:700;margin-bottom:4px" id="tkEmailSubjectPreview"></div>
                    <div id="tkEmailBodyPreview"></div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" style="align-self:flex-start" onclick="tkSendTestEmail()">Send Test Email to Me</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">WhatsApp Message</label>
                <textarea name="whatsapp_body" id="tkWhatsappBodyInput" rows="3" oninput="tkUpdatePreview()"
                          style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px;resize:vertical;font-family:var(--mono,monospace)">{{ $confirmPrefs['whatsapp_body'] }}</textarea>
                <div style="font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">Preview</div>
                <div style="padding:10px 12px;background:var(--bg-elevated);border:1px dashed var(--border-default);border-radius:var(--r-sm);font-size:13px;color:var(--text-200);white-space:pre-wrap" id="tkWhatsappBodyPreview"></div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('tenant.tickets.preferences.test-email') }}" id="tkTestEmailForm" style="display:none">
        @csrf
        <input type="hidden" name="email_subject" id="tkTestEmailSubject"/>
        <input type="hidden" name="email_body" id="tkTestEmailBody"/>
    </form>
</div>

<style>#confirmTemplates.show { display:flex !important; }</style>

<script>
const TK_SAMPLE_DATA = {
    contact_name: 'Ramesh Kumar',
    ticket_number: 'TKT-20260822-0001',
    subject: 'Unable to login to my account',
    tenant_name: @json(auth()->user()->tenant->name ?? 'Your Business'),
    tracking_link: '#',
};

function tkFillSample(template) {
    return String(template || '').replace(/\{\{(\w+)\}\}/g, (match, key) => TK_SAMPLE_DATA[key] ?? match);
}

function tkUpdatePreview() {
    document.getElementById('tkEmailSubjectPreview').textContent = tkFillSample(document.getElementById('tkEmailSubjectInput').value);
    document.getElementById('tkEmailBodyPreview').innerHTML = tkFillSample(document.getElementById('tkEmailBodyInput').value);
    document.getElementById('tkWhatsappBodyPreview').textContent = tkFillSample(document.getElementById('tkWhatsappBodyInput').value);
}

function tkSendTestEmail() {
    document.getElementById('tkTestEmailSubject').value = document.getElementById('tkEmailSubjectInput').value;
    document.getElementById('tkTestEmailBody').value = document.getElementById('tkEmailBodyInput').value;
    document.getElementById('tkTestEmailForm').submit();
}

tkUpdatePreview();
</script>

<div class="status-tabs">
    <a href="{{ route('tenant.tickets.index', ['status'=>'open']) }}" class="s-tab {{ $status === 'open' ? 'active' : '' }}">Open ({{ $counts['open'] }})</a>
    <a href="{{ route('tenant.tickets.index', ['status'=>'in_progress']) }}" class="s-tab {{ $status === 'in_progress' ? 'active' : '' }}">In Progress ({{ $counts['in_progress'] }})</a>
    <a href="{{ route('tenant.tickets.index', ['status'=>'resolved']) }}" class="s-tab {{ $status === 'resolved' ? 'active' : '' }}">Resolved ({{ $counts['resolved'] }})</a>
    <a href="{{ route('tenant.tickets.index', ['status'=>'closed']) }}" class="s-tab {{ $status === 'closed' ? 'active' : '' }}">Closed ({{ $counts['closed'] }})</a>
    <a href="{{ route('tenant.tickets.index', ['status'=>'mine']) }}" class="s-tab {{ $status === 'mine' ? 'active' : '' }}">Mine ({{ $counts['mine'] }})</a>
</div>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>Ticket #</th>
            <th>Subject</th>
            <th>Contact</th>
            <th>Priority</th>
            <th>Assigned</th>
            <th>Created</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tickets as $t)
        <tr onclick="window.location='{{ route('tenant.tickets.show', $t->id) }}'">
            <td class="mono" data-label="Ticket #">{{ $t->ticket_number ?? '—' }}</td>
            <td style="font-weight:600" data-label="Subject">{{ $t->subject }}</td>
            <td data-label="Contact">{{ $t->contact?->name ?? '—' }}</td>
            <td data-label="Priority"><span class="badge-priority badge-{{ $t->priority }}">{{ $t->priority }}</span></td>
            <td data-label="Assigned">{{ $t->assignee?->name ?? '—' }}</td>
            <td class="mono" data-label="Created">{{ $t->created_at->format('d M Y') }}</td>
            <td data-label="Status"><span class="badge-status badge-{{ $t->status }}">{{ \App\Models\Ticket::statuses()[$t->status] ?? ucfirst($t->status) }}</span></td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:40px;color:var(--text-400)">
                No tickets yet. <a href="{{ route('tenant.tickets.create') }}" style="color:var(--accent)">Create one</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $tickets->links() }}</div>

@endsection
