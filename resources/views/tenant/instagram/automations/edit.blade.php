@extends('layouts.app')
@section('title', 'Edit Automation')

@push('styles')
<style>
.form-section { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:24px; margin-bottom:16px; }
.form-section-title { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.form-section-title span { width:24px; height:24px; border-radius:var(--r-sm); background:var(--accent); color:#fff; font-size:12px; display:flex; align-items:center; justify-content:center; font-weight:700; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:600px){ .form-row { grid-template-columns:1fr; } }
.action-field { display:none; }
.action-field.visible { display:block; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit: {{ $automation->name }}</h1>
        <p class="page-sub">Triggered {{ $automation->triggered_count }} times</p>
    </div>
    <a href="{{ route('tenant.instagram.automations') }}" class="btn btn-ghost">Cancel</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('tenant.instagram.automations.update', $automation->id) }}">
@csrf @method('PUT')

<div class="form-section">
    <div class="form-section-title"><span>1</span> Basic Info</div>
    <div class="form-group">
        <label class="form-label">Automation Name <span class="required">*</span></label>
        <input type="text" name="name" class="form-input" value="{{ old('name', $automation->name) }}" required>
    </div>
</div>

<div class="form-section">
    <div class="form-section-title"><span>2</span> Trigger</div>
    <div class="form-group">
        <label class="form-label">Trigger Type <span class="required">*</span></label>
        <select name="trigger_type" id="triggerType" class="form-input" onchange="updateTrigger(this.value)" required>
            <option value="any_post_comment" {{ old('trigger_type',$automation->trigger_type)==='any_post_comment'?'selected':'' }}>Comment on ANY post</option>
            <option value="specific_post_comment" {{ old('trigger_type',$automation->trigger_type)==='specific_post_comment'?'selected':'' }}>Comment on SPECIFIC post</option>
            <option value="dm_keyword" {{ old('trigger_type',$automation->trigger_type)==='dm_keyword'?'selected':'' }}>DM contains keyword</option>
        </select>
    </div>

    <div id="postIdField" class="form-group action-field">
        <label class="form-label">Post ID</label>
        <input type="text" name="post_id" class="form-input" value="{{ old('post_id', $automation->post_id) }}">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Trigger Keywords (comma separated)</label>
            <input type="text" name="trigger_keywords" class="form-input"
                value="{{ old('trigger_keywords', implode(', ', $automation->trigger_keywords ?? [])) }}"
                placeholder="price, buy, info">
        </div>
        <div class="form-group">
            <label class="form-label">Keyword Match</label>
            <select name="keyword_match" class="form-input">
                <option value="contains" {{ old('keyword_match',$automation->keyword_match)==='contains'?'selected':'' }}>Contains</option>
                <option value="exact" {{ old('keyword_match',$automation->keyword_match)==='exact'?'selected':'' }}>Exact match</option>
                <option value="any" {{ old('keyword_match',$automation->keyword_match)==='any'?'selected':'' }}>Any word</option>
            </select>
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-title"><span>3</span> Action</div>
    <div class="form-group">
        <label class="form-label">Action Type <span class="required">*</span></label>
        <select name="action_type" id="actionType" class="form-input" onchange="updateAction(this.value)" required>
            <option value="send_dm" {{ old('action_type',$automation->action_type)==='send_dm'?'selected':'' }}>Send DM to commenter</option>
            <option value="reply_comment" {{ old('action_type',$automation->action_type)==='reply_comment'?'selected':'' }}>Reply to comment</option>
            <option value="trigger_n8n" {{ old('action_type',$automation->action_type)==='trigger_n8n'?'selected':'' }}>Trigger n8n workflow</option>
        </select>
    </div>

    <div id="dmField" class="form-group action-field">
        <label class="form-label">DM Message</label>
        <textarea name="dm_message" class="form-input" rows="4">{{ old('dm_message', $automation->dm_message) }}</textarea>
    </div>

    <div id="commentField" class="form-group action-field">
        <label class="form-label">Comment Reply</label>
        <textarea name="comment_reply" class="form-input" rows="3">{{ old('comment_reply', $automation->comment_reply) }}</textarea>
    </div>

    <div id="n8nField" class="form-group action-field">
        <label class="form-label">n8n Webhook URL</label>
        <input type="url" name="n8n_webhook_url" class="form-input" value="{{ old('n8n_webhook_url', $automation->n8n_webhook_url) }}">
    </div>
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;">
    <a href="{{ route('tenant.instagram.automations') }}" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</form>
@endsection

@push('scripts')
<script>
function updateTrigger(val) {
    document.getElementById('postIdField').classList.toggle('visible', val === 'specific_post_comment');
}
function updateAction(val) {
    document.getElementById('dmField').classList.toggle('visible', val === 'send_dm');
    document.getElementById('commentField').classList.toggle('visible', val === 'reply_comment');
    document.getElementById('n8nField').classList.toggle('visible', val === 'trigger_n8n');
}
updateTrigger(document.getElementById('triggerType').value);
updateAction(document.getElementById('actionType').value);
</script>
@endpush
