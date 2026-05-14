@extends('layouts.app')
@section('title', 'Send Email')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"/>
<style>
.send-layout { display:grid; grid-template-columns:1fr 320px; gap:16px; }
@media(max-width:1024px) { .send-layout { grid-template-columns:1fr; } }

.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.form-section { padding:20px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.fs-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:14px; }

.field { display:flex; flex-direction:column; gap:7px; margin-bottom:14px; }
.field:last-child { margin-bottom:0; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; }
.field-input:focus { border-color:var(--accent); }
.field-select { -webkit-appearance:none; cursor:pointer; }

.var-chip { font-size:11px; font-family:var(--mono); padding:3px 8px; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:4px; color:var(--accent); cursor:pointer; display:inline-block; margin:2px; transition:all .15s; }
.var-chip:hover { border-color:var(--accent); background:var(--accent-dim); }

.tpl-chip { padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid var(--border-default); background:none; cursor:pointer; color:var(--text-200); transition:all .15s; font-family:var(--font); }
.tpl-chip:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.tpl-chip.active { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }

/* Quill */
.quill-wrap { border:1.5px solid var(--border-default); border-radius:var(--r-sm); overflow:hidden; }
.quill-wrap .ql-toolbar { background:var(--bg-elevated); border:none; border-bottom:1px solid var(--border-subtle); padding:8px 10px; }
.quill-wrap .ql-container { border:none; font-size:14px; background:var(--bg-input); }
.quill-wrap .ql-editor { color:var(--text-100); min-height:200px; padding:14px 16px; line-height:1.7; }
.quill-wrap .ql-editor.ql-blank::before { color:var(--text-400); font-style:normal; }
.quill-wrap .ql-stroke { stroke:var(--text-300) !important; }
.quill-wrap .ql-fill   { fill:var(--text-300) !important; }
.quill-wrap .ql-picker-label { color:var(--text-300) !important; }

/* Email preview */
.email-preview-card {
    margin:0; border:1px solid var(--border-subtle);
    border-radius:var(--r-sm); overflow:hidden;
    background:#fff; min-height:200px;
}
.email-preview-header { background:#f8fafc; padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:12px; color:#4b5563; }
.email-preview-body   { padding:16px; font-size:13.5px; color:#1a1a1a; line-height:1.7; }

.form-actions { padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:10px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.email.index') }}" style="color:var(--text-300);text-decoration:none">Email</a>
            <span style="margin:0 6px">›</span> Send
        </div>
        <div class="page-title">Send Email</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.email.bulk') }}" class="btn btn-secondary">Bulk Send</a>
        <a href="{{ route('tenant.email.templates') }}" class="btn btn-secondary">Templates</a>
    </div>
</div>

<div class="send-layout">

    <div class="form-card">
        <form method="POST" action="{{ route('tenant.email.send.store') }}" id="sendForm">
            @csrf

            {{-- Recipient --}}
            <div class="form-section">
                <div class="fs-title">Recipient</div>
                <div class="field">
                    <label class="field-label">Quick Select</label>
                    <select class="field-input field-select" onchange="fillRecipient(this)">
                        <option value="">— Select or enter manually —</option>
                        @if($leads->isNotEmpty())
                        <optgroup label="Leads">
                            @foreach($leads as $l)
                            <option value="{{ $l->email }}|{{ $l->name }}|lead|{{ $l->id }}">
                                {{ $l->name }} — {{ $l->email }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                        @if($contacts->isNotEmpty())
                        <optgroup label="Contacts">
                            @foreach($contacts as $c)
                            <option value="{{ $c->email }}|{{ $c->name }}|contact|{{ $c->id }}">
                                {{ $c->name }} — {{ $c->email }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="field">
                        <label class="field-label">Email <span class="req">*</span></label>
                        <input type="email" name="to_email" id="toEmail" class="field-input"
                               placeholder="recipient@email.com"
                               value="{{ $lead?->email ?? $contact?->email }}" required
                               oninput="updatePreview()"/>
                    </div>
                    <div class="field">
                        <label class="field-label">Name</label>
                        <input type="text" name="to_name" id="toName" class="field-input"
                               placeholder="Recipient name"
                               value="{{ $lead?->name ?? $contact?->name }}"
                               oninput="updatePreview()"/>
                    </div>
                </div>
                <input type="hidden" name="lead_id"    id="leadId"    value="{{ $lead?->id }}"/>
                <input type="hidden" name="contact_id" id="contactId" value="{{ $contact?->id }}"/>
            </div>

            {{-- Template --}}
            <div class="form-section">
                <div class="fs-title">Template (Optional)</div>
                <input type="hidden" name="template_id" id="templateId"/>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach($templates as $tpl)
                    <button type="button" class="tpl-chip" id="chip_{{ $tpl->id }}"
                            onclick='selectTemplate({{ $tpl->id }}, @json($tpl->subject), @json($tpl->body))'>
                        {{ $tpl->name }}
                    </button>
                    @endforeach
                    <button type="button" class="tpl-chip" onclick="clearTemplate()">✕ Clear</button>
                </div>
            </div>

            {{-- Subject + Body --}}
            <div class="form-section">
                <div class="fs-title">Email Content</div>

                <div class="field">
                    <label class="field-label">Subject <span class="req">*</span></label>
                    <input type="text" name="subject" id="emailSubject" class="field-input"
                           placeholder="Email subject..." required oninput="updatePreview()"/>
                </div>

                {{-- Variable insert --}}
                <div style="margin-bottom:10px">
                    <div style="font-size:11.5px;color:var(--text-300);margin-bottom:5px">Insert variable:</div>
                    @foreach(\App\Models\EmailTemplate::variables() as $var => $desc)
                    <span class="var-chip" onclick="insertVar('{{ $var }}')" title="{{ $desc }}">{{ $var }}</span>
                    @endforeach
                </div>

                <div class="field">
                    <label class="field-label">Body <span class="req">*</span></label>
                    <textarea name="body" id="emailBodyHidden" style="display:none" required></textarea>
                    <div class="quill-wrap">
                        <div id="quillEmailSend"></div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('tenant.email.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" onclick="return syncBody()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                    Send Email
                </button>
            </div>
        </form>
    </div>

    {{-- Preview panel --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;position:sticky;top:80px">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-subtle)">
            <div style="font-size:13px;font-weight:700;color:var(--text-100)">Email Preview</div>
            <div style="font-size:12px;color:var(--text-300);margin-top:2px">Preview before sending</div>
        </div>
        <div style="padding:14px">
            <div class="email-preview-card">
                <div class="email-preview-header">
                    <div><strong>To:</strong> <span id="prevTo">—</span></div>
                    <div><strong>Subject:</strong> <span id="prevSubject">—</span></div>
                </div>
                <div class="email-preview-body" id="prevBody">
                    Your email body will appear here...
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
const quillEmailSend = new Quill('#quillEmailSend', {
    theme: 'snow',
    placeholder: 'Dear @{{name}}, ...',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

quillEmailSend.on('text-change', updatePreview);

function syncBody() {
    const html = quillEmailSend.root.innerHTML.trim();
    if (!html || html === '<p><br></p>') { alert('Email body is required.'); return false; }
    document.getElementById('emailBodyHidden').value = html;
    return true;
}

function insertVar(v) {
    quillEmailSend.focus();
    const range = quillEmailSend.getSelection() || { index: quillEmailSend.getLength() };
    quillEmailSend.insertText(range.index, v, 'user');
    quillEmailSend.setSelection(range.index + v.length);
    updatePreview();
}

function fillRecipient(sel) {
    if (!sel.value) return;
    const [email, name, type, id] = sel.value.split('|');
    document.getElementById('toEmail').value   = email;
    document.getElementById('toName').value    = name;
    document.getElementById('leadId').value    = type === 'lead'    ? id : '';
    document.getElementById('contactId').value = type === 'contact' ? id : '';
    updatePreview();
}

function selectTemplate(id, subject, body) {
    document.querySelectorAll('.tpl-chip').forEach(c => c.classList.remove('active'));
    document.getElementById('chip_' + id).classList.add('active');
    document.getElementById('templateId').value      = id;
    document.getElementById('emailSubject').value    = subject;
    quillEmailSend.root.innerHTML = body;
    updatePreview();
}

function clearTemplate() {
    document.querySelectorAll('.tpl-chip').forEach(c => c.classList.remove('active'));
    document.getElementById('templateId').value   = '';
    document.getElementById('emailSubject').value = '';
    quillEmailSend.setContents([]);
    updatePreview();
}

function updatePreview() {
    const email   = document.getElementById('toEmail').value;
    const name    = document.getElementById('toName').value;
    const subject = document.getElementById('emailSubject').value;
    const html    = quillEmailSend.root.innerHTML;

    document.getElementById('prevTo').textContent      = name ? `${name} <${email}>` : email || '—';
    document.getElementById('prevSubject').textContent = subject || '—';
    document.getElementById('prevBody').innerHTML      = html || 'Your email body will appear here...';
}

document.getElementById('toEmail').addEventListener('input', updatePreview);
document.getElementById('toName').addEventListener('input', updatePreview);
document.getElementById('emailSubject').addEventListener('input', updatePreview);
</script>
@endpush