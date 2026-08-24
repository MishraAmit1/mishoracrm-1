<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Support — {{ $tenant->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
<style>
body { background: var(--bg-app); min-height:100vh; padding:24px 16px; }
.bk-wrap { max-width:560px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.bk-brand { text-align:center; padding:8px 0 4px; font-size:13px; color:var(--text-300); }
.bk-title { text-align:center; font-size:20px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; padding:22px; }
.bk-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input { width:100%; padding:10px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.bk-btn { width:100%; padding:12px; border-radius:10px; border:none; background:var(--accent); color:#fff; font-size:14px; font-weight:600; cursor:pointer; }
</style>
</head>
<body>

<div class="bk-wrap">
    <div class="bk-brand">Contact support for</div>
    <div class="bk-title">{{ $tenant->name }}</div>

    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        <form method="POST" action="{{ route('public.support.store', $token) }}" enctype="multipart/form-data">
            @csrf

            <div class="bk-field">
                <label>Subject <span style="color:var(--red)">*</span></label>
                <input type="text" name="subject" class="bk-input" required value="{{ old('subject') }}"/>
            </div>

            @if($services->isNotEmpty())
            <div class="bk-field">
                <label>Related Service <span style="font-weight:400;text-transform:none">(optional)</span></label>
                <select name="service_id" class="bk-input">
                    <option value="">— None —</option>
                    @foreach($services as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="bk-field">
                <label>Describe the issue <span style="color:var(--red)">*</span></label>
                <textarea name="description" class="bk-input" rows="5" required>{{ old('description') }}</textarea>
            </div>

            <div class="bk-field">
                <label>Attachments <span style="font-weight:400;text-transform:none">(optional, max 5 files, 10MB each)</span></label>
                <input type="file" name="attachments[]" class="bk-input" multiple/>
            </div>

            <div class="bk-field">
                <label>Your Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="bk-input" required value="{{ old('name') }}"/>
            </div>

            <div class="bk-field">
                <label>Phone <span style="color:var(--red)">*</span></label>
                <input type="tel" name="phone" class="bk-input" required value="{{ old('phone') }}"/>
            </div>

            <div class="bk-field">
                <label>Email <span style="font-weight:400;text-transform:none">(optional)</span></label>
                <input type="email" name="email" class="bk-input" value="{{ old('email') }}"/>
            </div>

            <button type="submit" class="bk-btn">Submit Ticket</button>
        </form>
    </div>
</div>

</body>
</html>
