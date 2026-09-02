<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"/>
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<title>Reset Password — Milan CRM</title>
<script>
(function () {
  var saved = localStorage.getItem('crm_theme');
  if (saved) document.documentElement.dataset.theme = saved;
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/auth.css') }}"/>
<style>
/* Centered single-column layout for this page */
.auth-shell { grid-template-columns: 1fr; }
.auth-visual { display: none !important; }
.auth-form-panel {
  min-height: 100vh;
  background: var(--bg-base);
  justify-content: center;
  position: relative;
}
/* Subtle radial bg */
.auth-form-panel::after {
  content: '';
  position: absolute; inset: 0; z-index: 0;
  background:
    radial-gradient(ellipse 50% 50% at 50% 30%, rgba(99,120,255,0.08) 0%, transparent 65%);
  pointer-events: none;
}
.auth-form-panel > * { position: relative; z-index: 1; }
.auth-card { max-width: 400px; }

/* Back link */
.back-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--text-300);
  text-decoration: none; margin-bottom: 32px;
  transition: color var(--t-fast) var(--ease);
}
.back-link:hover { color: var(--text-100); }
.back-link svg { width: 14px; height: 14px; }

/* Icon display */
.page-icon {
  width: 56px; height: 56px;
  background: var(--accent-dim);
  border: 1px solid rgba(99,120,255,0.2);
  border-radius: var(--r-lg);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 20px;
}
.page-icon svg { width: 26px; height: 26px; color: var(--accent); }

/* Email sent state */
.sent-state { text-align: center; }
.sent-icon {
  width: 64px; height: 64px;
  background: rgba(45,212,160,0.1);
  border: 1px solid rgba(45,212,160,0.2);
  border-radius: var(--r-lg);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
}
.sent-icon svg { width: 28px; height: 28px; color: var(--green); }
.sent-state h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
.sent-state p { font-size: 14px; color: var(--text-200); line-height: 1.6; margin-bottom: 24px; }
.sent-state strong { color: var(--text-100); }

/* Info box */
.info-box {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--r-sm);
  padding: 14px 16px;
  margin-bottom: 20px;
  text-align: left;
}
.info-box p { font-size: 12.5px; color: var(--text-200); line-height: 1.7; }
.info-box .info-row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px; }
.info-box .info-row:last-child { margin-bottom: 0; }
.info-box .dot { width: 4px; height: 4px; border-radius: 50%; background: var(--text-300); flex-shrink: 0; margin-top: 7px; }

/* Countdown */
.resend-line { font-size: 13px; color: var(--text-300); text-align: center; margin-top: 16px; }
.resend-btn { background: none; border: none; color: var(--accent); font-size: 13px; font-weight: 600; cursor: pointer; font-family: var(--font); padding: 0; }
.resend-btn:disabled { color: var(--text-300); cursor: not-allowed; }

/* Logo centered */
.center-logo {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  margin-bottom: 40px;
}
</style>
</head>
<body>

<div class="auth-shell">
  <div class="auth-form-panel">

    <button class="theme-btn" onclick="toggleTheme()" type="button" style="position:fixed;">
      <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
      <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
    </button>

    <div class="auth-card">

      {{-- Logo --}}
      <div class="center-logo">
        @include('components.brand-logo', ['h' => 28])
      </div>

      {{-- ── Form state (before submit) ── --}}
      @if(!session('status'))
      <div id="formState">

        <a href="{{ route('login') }}" class="back-link">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
          Back to login
        </a>

        <div class="page-icon">
          <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
        </div>

        <div class="card-head">
          <h1>Forgot password?</h1>
          <p>Enter your registered email and we'll send a secure reset link right away.</p>
        </div>

        @if ($errors->any())
        <div class="alert alert-error">
          <span class="alert-icon">⚠</span>
          <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="fpForm">
          @csrf

          <div class="field" style="margin-bottom:20px">
            <label class="field-label" for="email">Registered email address</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
              </span>
              <input id="email" name="email" type="email" class="field-input @error('email') has-error @enderror"
                placeholder="you@company.com" value="{{ old('email') }}" autocomplete="email" required/>
            </div>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <button type="submit" class="btn-cta" id="sendBtn">Send reset link</button>

        </form>

        <div class="form-foot">
          Remembered it? <a href="{{ route('login') }}" class="a-link">Sign in →</a>
        </div>
      </div>
      @endif

      {{-- ── Success state (after submit) ── --}}
      @if(session('status'))
      <div class="sent-state" id="sentState">

        <div class="sent-icon">
          <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z"/></svg>
        </div>

        <h1>Check your inbox</h1>
        <p>We sent a password reset link to<br/><strong>{{ old('email', request()->get('email', '—')) }}</strong><br/>It expires in 60 minutes.</p>

        <div class="info-box">
          <div class="info-row"><div class="dot"></div><p>Check your spam or junk folder</p></div>
          <div class="info-row"><div class="dot"></div><p>Email sent from <span style="font-family:var(--mono);font-size:11.5px;color:var(--accent)">noreply@milancrm.in</span></p></div>
          <div class="info-row"><div class="dot"></div><p>Link is valid for 60 minutes only</p></div>
        </div>

        <a href="{{ route('login') }}" class="btn-ghost" style="margin-bottom:0">← Back to login</a>

        <div class="resend-line">
          Didn't receive it?
          <button class="resend-btn" id="resendBtn" onclick="resendEmail()" disabled>
            Resend in <span id="cdTimer">60</span>s
          </button>
        </div>

      </div>
      @endif

    </div>
  </div>
</div>

<script>
// Theme
function toggleTheme() {
  const html = document.documentElement;
  const isD = html.dataset.theme === 'dark';
  html.dataset.theme = isD ? 'light' : 'dark';
  document.getElementById('ico-moon').style.display = isD ? 'none' : '';
  document.getElementById('ico-sun').style.display  = isD ? '' : 'none';
  localStorage.setItem('crm_theme', isD ? 'light' : 'dark');
}
(function() {
  // Theme itself is already applied in <head> to avoid a flash; just sync the icon.
  if (document.documentElement.dataset.theme === 'light') { document.getElementById('ico-moon').style.display='none'; document.getElementById('ico-sun').style.display=''; }
})();

// Submit loading
const fpForm = document.getElementById('fpForm');
if (fpForm) {
  fpForm.addEventListener('submit', function() {
    const btn = document.getElementById('sendBtn');
    btn.innerHTML = '<span class="spin"></span>Sending...';
    btn.disabled = true;
  });
}

// Countdown + resend
let secs = 60;
const cdEl  = document.getElementById('cdTimer');
const rdBtn = document.getElementById('resendBtn');
if (cdEl) {
  const iv = setInterval(() => {
    secs--;
    cdEl.textContent = secs;
    if (secs <= 0) {
      clearInterval(iv);
      rdBtn.disabled = false;
      rdBtn.textContent = 'Resend now';
    }
  }, 1000);
}
function resendEmail() {
  if (fpForm) { fpForm.submit(); } else { window.location.href = '{{ route("password.request") }}'; }
}
</script>
</body>
</html>