<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign In — CrmPro</title>
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
/* Page-specific overrides */
.auth-form-panel { justify-content: center; }
</style>
</head>
<body>

<div class="auth-shell">

  {{-- ── Left visual panel ── --}}
  <div class="auth-visual">
    <div class="visual-grid"></div>
    <div class="geo-shape geo-1"></div>
    <div class="geo-shape geo-2"></div>
    <div class="geo-shape geo-3"></div>

    <div class="visual-content">

      <div class="v-logo">
        <div class="v-logo-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
        </div>
        <span class="v-logo-name">Crm<span>Pro</span></span>
      </div>

      <div class="v-hero">
        <h2>Your business,<br/><em>fully in control</em></h2>
        <p>Manage leads, deals, invoices, and your team — all from one powerful workspace built for Indian businesses.</p>
      </div>

      <div class="v-features" style="margin-bottom:32px">
        <div class="v-feature">
          <div class="v-feature-dot"></div>
          <span>Multi-tenant workspace — one login, full access</span>
        </div>
        <div class="v-feature">
          <div class="v-feature-dot" style="background:var(--green);box-shadow:0 0 8px rgba(45,212,160,0.4)"></div>
          <span>WhatsApp & Email campaigns in seconds</span>
        </div>
        <div class="v-feature">
          <div class="v-feature-dot" style="background:var(--amber);box-shadow:0 0 8px rgba(248,184,78,0.4)"></div>
          <span>Real-time reports & pipeline visibility</span>
        </div>
        <div class="v-feature">
          <div class="v-feature-dot"></div>
          <span>Attendance, tasks, and staff management</span>
        </div>
      </div>

      <div class="v-stats">
        <div class="v-stat">
          <div class="v-stat-num">12k+</div>
          <div class="v-stat-label">Users</div>
        </div>
        <div class="v-stat">
          <div class="v-stat-num">99.9%</div>
          <div class="v-stat-label">Uptime</div>
        </div>
        <div class="v-stat">
          <div class="v-stat-num">4.8★</div>
          <div class="v-stat-label">Rating</div>
        </div>
      </div>

    </div>
  </div>

  {{-- ── Right form panel ── --}}
  <div class="auth-form-panel">

    <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button">
      <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
      <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
    </button>

    <div class="auth-card">

      {{-- Mobile logo --}}
      <div class="mobile-logo">
        <div class="v-logo-icon" style="width:32px;height:32px;">
          <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
        </div>
        <span class="v-logo-name" style="font-size:16px;">Crm<span>Pro</span></span>
      </div>

      <div class="card-head">
        <div class="card-tag"><div class="card-tag-dot"></div> Secure login</div>
        <h1>Welcome back</h1>
        <p>Sign in to your workspace to continue</p>
      </div>

      {{-- Error / Success alerts --}}
      @if ($errors->any())
      <div class="alert alert-error">
        <span class="alert-icon">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </span>
        <span>{{ $errors->first() }}</span>
      </div>
      @endif
      @if (session('success'))
      <div class="alert alert-success">
        <span class="alert-icon">✓</span>
        <span>{{ session('success') }}</span>
      </div>
      @endif

      <form method="POST" action="{{ route('login.store') }}" id="loginForm" novalidate>
        @csrf

        <div class="field">
          <label class="field-label" for="email">Email address</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            </span>
            <input
              id="email" name="email" type="email"
              class="field-input @error('email') has-error @enderror"
              placeholder="you@company.com"
              value="{{ old('email') }}"
              autocomplete="email" required
            />
          </div>
          @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
          <label class="field-label" for="password">Password</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </span>
            <input
              id="password" name="password" type="password"
              class="field-input @error('password') has-error @enderror"
              placeholder="Enter your password"
              autocomplete="current-password" required
            />
            <button type="button" class="eye-btn" onclick="toggleEye('password', this)">
              <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
          </div>
          @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-meta">
          <label class="check-label">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}/>
            <span>Remember me</span>
          </label>
          <a href="{{ route('password.request') }}" class="a-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn-cta" id="submitBtn">
          Sign in to workspace
        </button>

      </form>

      <div class="form-foot">
        New to CrmPro? <a href="{{ route('register') }}" class="a-link">Create free account →</a>
      </div>

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
  if (document.documentElement.dataset.theme === 'light') {
    document.getElementById('ico-moon').style.display = 'none';
    document.getElementById('ico-sun').style.display  = '';
  }
})();

// Password eye
function toggleEye(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.querySelector('svg').style.opacity = inp.type === 'text' ? '0.5' : '1';
}

// Submit loading
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<span class="spin"></span>Signing in...';
  btn.disabled = true;
});
</script>
</body>
</html>