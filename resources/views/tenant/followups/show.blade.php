@extends('layouts.app')
@section('title', 'Follow-up Detail')

@push('styles')
<style>
.show-layout { display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start; }
@media(max-width:1024px) { .show-layout { grid-template-columns:1fr; } }

.detail-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.detail-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.detail-title { font-size:13.5px; font-weight:700; color:var(--text-100); }
.detail-body { padding:20px; }

.info-row { display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.info-row:last-child { border-bottom:none; }
.info-label { font-size:12px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:0.4px; min-width:120px; flex-shrink:0; margin-top:1px; }
.info-value { font-size:13.5px; color:var(--text-100); flex:1; line-height:1.5; }

.type-badge {
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 14px; border-radius:20px; font-size:13px; font-weight:600;
}

.quick-action {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:var(--r-sm);
    border:1.5px solid var(--border-default);
    background:none; cursor:pointer; width:100%;
    font-family:var(--font); font-size:13px; font-weight:500;
    color:var(--text-200); text-align:left; text-decoration:none;
    transition:all 0.15s var(--ease); margin-bottom:8px;
}
.quick-action:last-child { margin-bottom:0; }
.quick-action:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.quick-action svg { width:15px; height:15px; flex-shrink:0; }
.quick-action.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }

/* Mark done form */
.done-form {
    background:var(--bg-elevated); border:1px solid var(--border-default);
    border-radius:var(--r-md); padding:16px; margin-bottom:16px;
}
.done-form-title { font-size:13px; font-weight:700; color:var(--text-100); margin-bottom:12px; }
.done-input {
    width:100%; padding:10px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13.5px; outline:none; resize:vertical; min-height:80px;
    transition:border-color 0.15s var(--ease); margin-bottom:10px;
}
.done-input:focus { border-color:var(--accent); }
.done-input::placeholder { color:var(--text-400); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.followups.index') }}" style="color:var(--text-300);text-decoration:none">Follow-ups</a>
            <span style="margin:0 6px">›</span>
            <span>Detail</span>
        </div>
        <div class="page-title">Follow-up Detail</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.followups.edit', $followup) }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
            Edit
        </a>
    </div>
</div>

<div class="show-layout">

    {{-- Left --}}
    <div>

        {{-- Main detail card --}}
        <div class="detail-card">
            <div class="detail-head">
                <div class="detail-title">Follow-up Information</div>
                @php
                    $statusColors = [
                        'scheduled'   => ['bg'=>'var(--accent-dim)',  'color'=>'var(--accent)'],
                        'done'        => ['bg'=>'var(--green-dim)',   'color'=>'var(--green)'],
                        'missed'      => ['bg'=>'var(--red-dim)',     'color'=>'var(--red)'],
                        'rescheduled' => ['bg'=>'var(--amber-dim)',   'color'=>'var(--amber)'],
                    ];
                    $sc = $statusColors[$followup->status] ?? $statusColors['scheduled'];
                @endphp
                <span class="badge"
                      style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700">
                    {{ ucfirst($followup->status) }}
                </span>
            </div>
            <div class="detail-body">

                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="info-value">
                        @php
                            $typeBg = [
                                'call'=>'var(--green-dim)', 'email'=>'var(--accent-dim)',
                                'whatsapp'=>'var(--green-dim)', 'meeting'=>'var(--purple-dim)', 'other'=>'var(--amber-dim)'
                            ];
                            $typeC = [
                                'call'=>'var(--green)', 'email'=>'var(--accent)',
                                'whatsapp'=>'var(--green)', 'meeting'=>'var(--purple)', 'other'=>'var(--amber)'
                            ];
                        @endphp
                        <span class="type-badge"
                              style="background:{{ $typeBg[$followup->type] ?? 'var(--bg-elevated)' }};color:{{ $typeC[$followup->type] ?? 'var(--text-200)' }}">
                            {{  ucfirst($followup->type) }}
                        </span>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Scheduled At</span>
                    <span class="info-value">
                        {{ $followup->scheduled_at->format('d M Y, h:i A') }}
                        <span style="font-size:12px;color:var(--text-400);margin-left:6px">
                            ({{ $followup->scheduled_at->diffForHumans() }})
                        </span>
                        @if($followup->isOverdue())
                            <span style="color:var(--red);font-size:12px;margin-left:4px">· Overdue</span>
                        @endif
                    </span>
                </div>

                @if($followup->done_at)
                <div class="info-row">
                    <span class="info-label">Completed At</span>
                    <span class="info-value td-mono" style="font-size:13px">
                        {{ $followup->done_at->format('d M Y, h:i A') }}
                    </span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">Assigned To</span>
                    <span class="info-value">{{ $followup->assignedTo?->name ?? '—' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Created By</span>
                    <span class="info-value">{{ $followup->createdBy?->name ?? '—' }}</span>
                </div>

                @if($followup->lead)
                <div class="info-row">
                    <span class="info-label">Lead</span>
                    <span class="info-value">
                        <a href="{{ route('tenant.leads.show', $followup->lead_id) }}"
                           style="color:var(--accent);text-decoration:none;font-weight:600">
                            {{ $followup->lead->name }}
                        </a>
                        <span style="font-size:12px;color:var(--text-400);margin-left:4px">{{ $followup->lead->phone }}</span>
                    </span>
                </div>
                @endif

                @if($followup->contact)
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span class="info-value">
                        <a href="{{ route('tenant.contacts.show', $followup->contact_id) }}"
                           style="color:var(--accent);text-decoration:none;font-weight:600">
                            {{ $followup->contact->name }}
                        </a>
                    </span>
                </div>
                @endif

                @if($followup->notes)
                <div class="info-row">
                    <span class="info-label">Notes</span>
                    <span class="info-value" style="line-height:1.65">{{ $followup->notes }}</span>
                </div>
                @endif

                @if($followup->outcome)
                <div class="info-row">
                    <span class="info-label">Outcome</span>
                    <span class="info-value" style="line-height:1.65;color:var(--green)">{{ $followup->outcome }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">Created</span>
                    <span class="info-value td-mono" style="font-size:12.5px;color:var(--text-400)">
                        {{ $followup->created_at->format('d M Y, h:i A') }}
                    </span>
                </div>

            </div>
        </div>

    </div>

    {{-- Right sidebar --}}
    <div>

        {{-- Mark done form --}}
        @if($followup->isScheduled())
        <div class="done-form">
            <div class="done-form-title">✅ Mark as Done</div>
            <form method="POST" action="{{ route('followups.done', $followup) }}">
                @csrf
                <textarea name="outcome" class="done-input"
                          placeholder="What happened? Add outcome..."></textarea>
                <button type="submit" class="btn btn-primary" style="width:100%">
                    Mark Done
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('followups.missed', $followup) }}" style="margin-bottom:16px">
            @csrf
            <button type="submit" class="btn" style="width:100%;background:var(--red-dim);color:var(--red);border:1.5px solid rgba(255,82,87,0.3)"
                    onclick="return confirm('Mark as missed?')">
                ❌ Mark as Missed
            </button>
        </form>
        @endif

        {{-- Actions --}}
        <div class="detail-card">
            <div class="detail-head">
                <div class="detail-title">Actions</div>
            </div>
            <div class="detail-body" style="padding:12px">

                <a href="{{ route('tenant.followups.edit', $followup) }}" class="quick-action">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    Edit Follow-up
                </a>

                @if($followup->lead)
                <a href="{{ route('tenant.leads.show', $followup->lead_id) }}" class="quick-action">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    View Lead
                </a>
                @endif

                <a href="{{ route('tenant.followups.create', $followup->lead_id ? ['lead_id'=>$followup->lead_id] : []) }}"
                   class="quick-action">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Schedule Another
                </a>

                <form method="POST" action="{{ route('tenant.followups.destroy', $followup) }}"
                      onsubmit="return confirm('Delete this follow-up?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="quick-action danger" style="border-color:rgba(255,82,87,0.3);color:var(--red)">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Delete Follow-up
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>

@endsection