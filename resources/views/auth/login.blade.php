<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"/>
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<title>Sign In — Milan CRM</title>
<script>
(function () {
  var saved = localStorage.getItem('crm_theme');
  if (saved) document.documentElement.dataset.theme = saved;
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/auth.css') }}"/>
</head>
<body>

<div class="auth-shell">

  {{-- ── Left visual panel ── --}}
  <div class="auth-visual">
    <div class="visual-grid"></div>

    <div class="visual-content">

      <div class="v-logo">
        @include('components.brand-logo', ['h' => 30])
      </div>

      <div class="v-hero">
        <h2>Your whole business,<br/><em>one workspace</em></h2>
        <p>Leads, deals, invoices, WhatsApp campaigns and your team — running together, in real time.</p>
      </div>

      {{-- Product mock --}}
      <div class="v-mock" style="max-width:400px">
        <div class="v-mock-row">
          <div class="v-mock-tile">
            <div class="v-mock-l">Pipeline</div>
            <div class="v-mock-n">₹12.4L</div>
            <div class="v-mock-d up">▲ 18% this month</div>
          </div>
          <div class="v-mock-tile">
            <div class="v-mock-l">Deals won</div>
            <div class="v-mock-n">47</div>
            <div class="v-mock-bars"><i style="height:40%"></i><i style="height:65%"></i><i style="height:50%"></i><i style="height:100%"></i><i style="height:72%"></i><i style="height:85%"></i></div>
          </div>
        </div>
        <div class="v-mock-toast">
          <span class="tk"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg></span>
          <span>
            <span class="tt">Deal marked won</span><br/>
            <span class="tb">₹1,24,000</span>
          </span>
        </div>
      </div>

      <div class="v-features">
        <div class="v-feature">
          <span class="v-feature-ico"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></span>
          <span>Multi-tenant workspace — one login, full access</span>
        </div>
        <div class="v-feature">
          <span class="v-feature-ico"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg></span>
          <span>WhatsApp &amp; Email campaigns in seconds</span>
        </div>
        <div class="v-feature">
          <span class="v-feature-ico"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg></span>
          <span>Real-time reports &amp; pipeline visibility</span>
        </div>
      </div>

      <div class="v-trust" style="margin-top:auto">
        <div class="v-trust-avs">
          <span style="background:#6378ff">RS</span>
          <span style="background:#2dd4a0">NK</span>
          <span style="background:#f8b84e">AV</span>
          <span style="background:#a78bfa">PM</span>
        </div>
        <div class="v-trust-txt"><b>12,000+</b> teams run their business on Milan CRM</div>
      </div>

    </div>
  </div>

  {{-- ── Right form panel ── --}}
  <div class="auth-form-panel">

    <div class="fp-head">
      <a href="{{ route('home') }}" style="display:flex;align-items:center;text-decoration:none">
        @include('components.brand-logo', ['h' => 24])
      </a>
      <div class="fp-head-r">
        <a href="{{ route('pricing') }}" class="a-link">Pricing</a>
        <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button" style="position:static">
          <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
          <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
        </button>
      </div>
    </div>

    <div class="auth-card">

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

        <div class="auth-trust">
          <div class="auth-trust-avs">
            <span style="background:#6378ff">RS</span>
            <span style="background:#2dd4a0">NK</span>
            <span style="background:#f8b84e">AV</span>
          </div>
          <div class="auth-trust-txt"><b>12,000+</b> teams · SSL secured</div>
        </div>

      </form>

      <div class="form-foot">
        New to Milan CRM? <a href="{{ route('register') }}" class="a-link">Create free account →</a>
      </div>

    </div>

    <div class="fp-foot">
      <span>© {{ now()->year }} Milan CRM</span>
      <span><a href="/terms">Terms</a> · <a href="/privacy">Privacy</a></span>
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