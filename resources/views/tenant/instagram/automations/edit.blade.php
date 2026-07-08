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
.post-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:10px; margin-top:12px; max-height:280px; overflow-y:auto; padding:4px; }
.post-card { position:relative; border:2px solid transparent; border-radius:var(--r-md); overflow:hidden; cursor:grab; aspect-ratio:1/1; background:var(--bg-subtle); }
.post-card img { width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; }
.post-card.selected { border-color:var(--accent); }
.post-card-check { position:absolute; top:4px; right:4px; width:18px; height:18px; border-radius:50%; background:var(--accent); color:#fff; font-size:11px; align-items:center; justify-content:center; display:none; }
.post-card.selected .post-card-check { display:flex; }
.post-gallery-empty, .post-gallery-loading { font-size:12px; color:var(--text-300); padding:12px 4px; grid-column:1/-1; }
#postIdInput.drop-target { outline:2px dashed var(--accent); outline-offset:2px; }
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
        <label class="form-label">Post</label>
        <input type="text" name="post_id" id="postIdInput" class="form-input" value="{{ old('post_id', $automation->post_id) }}" placeholder="Instagram Post ID"
            ondragover="event.preventDefault(); this.classList.add('drop-target')"
            ondragleave="this.classList.remove('drop-target')"
            ondrop="handlePostDrop(event)">
        <span class="form-hint">Click or drag a post below to fill this in automatically</span>
        <div id="postGallery" class="post-gallery"></div>
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
    if (val === 'specific_post_comment') loadPosts();
}
function updateAction(val) {
    document.getElementById('dmField').classList.toggle('visible', val === 'send_dm');
    document.getElementById('commentField').classList.toggle('visible', val === 'reply_comment');
}
updateTrigger(document.getElementById('triggerType').value);
updateAction(document.getElementById('actionType').value);

// ── Post picker (click or drag a post to fill Post ID) ─────────
let postsLoaded = false;
function loadPosts() {
    if (postsLoaded) return;
    postsLoaded = true;
    const gallery = document.getElementById('postGallery');
    gallery.innerHTML = '<div class="post-gallery-loading">Loading your posts…</div>';
    fetch('{{ route('tenant.instagram.automations.posts') }}')
        .then(r => r.json())
        .then(data => renderPostGallery(data.posts || []))
        .catch(() => renderPostGallery([]));
}

function renderPostGallery(posts) {
    const gallery = document.getElementById('postGallery');
    if (!posts.length) {
        gallery.innerHTML = '<div class="post-gallery-empty">No posts found. Connect your Instagram account, or paste the Post ID manually above.</div>';
        return;
    }
    const currentId = document.getElementById('postIdInput').value;
    gallery.innerHTML = posts.map(function (p) {
        const thumb = p.thumbnail_url || p.media_url || '';
        const selected = p.id === currentId ? ' selected' : '';
        const caption = (p.caption || '').replace(/"/g, '&quot;').slice(0, 80);
        return '<div class="post-card' + selected + '" draggable="true" data-post-id="' + p.id + '" ' +
            'ondragstart="handlePostDragStart(event)" onclick="selectPost(\'' + p.id + '\')" title="' + caption + '">' +
            (thumb ? '<img src="' + thumb + '" alt="" loading="lazy">' : '') +
            '<span class="post-card-check">&#10003;</span></div>';
    }).join('');
}

function handlePostDragStart(e) {
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.postId);
}

function handlePostDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drop-target');
    const id = e.dataTransfer.getData('text/plain');
    if (id) selectPost(id);
}

function selectPost(id) {
    document.getElementById('postIdInput').value = id;
    document.querySelectorAll('#postGallery .post-card').forEach(function (el) {
        el.classList.toggle('selected', el.dataset.postId === id);
    });
}
</script>
@endpush
