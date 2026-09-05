<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"/>
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<title>Contact Sales — Milan CRM</title>
<script>
(function () {
  var saved = localStorage.getItem('crm_theme');
  if (saved) document.documentElement.dataset.theme = saved;
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) ?: '1' }}"/>
<style>
  body { overflow-x: hidden; }
  .cs-wrap { max-width: 1000px; margin: 0 auto; padding: 8px 24px 0; }
  .cs-grid { display: grid; grid-template-columns: 1fr 1.1fr; gap: 40px; align-items: start; }
  @media (max-width: 860px) { .cs-grid { grid-template-columns: 1fr; gap: 28px; } }
  .cs-points { list-style: none; padding: 0; margin: 22px 0 0; display: flex; flex-direction: column; gap: 16px; }
  .cs-points li { display: flex; gap: 12px; font-size: 14px; color: var(--text-200); line-height: 1.5; }
  .cs-points li svg { width: 18px; height: 18px; color: var(--accent); flex-shrink: 0; margin-top: 1px; }
  .cs-contact { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--border-default); font-size: 13px; color: var(--text-300); display: flex; flex-direction: column; gap: 8px; }
  .cs-contact a { color: var(--accent); text-decoration: none; }
  .cs-card { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: 18px; padding: 28px; }
  .cs-card .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 480px) { .cs-card .field-grid { grid-template-columns: 1fr; } }
  .cs-success { text-align: center; padding: 24px 8px; }
  .cs-success .cs-check { width: 52px; height: 52px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
  .cs-success .cs-check svg { width: 26px; height: 26px; color: #fff; }
  .cs-success h3 { margin: 0 0 6px; font-size: 18px; color: var(--text-100); }
  .cs-success p { margin: 0; font-size: 14px; color: var(--text-300); }
  textarea.field-input { min-height: 96px; resize: vertical; padding: 12px 14px; }
</style>
</head>
<body>
<div class="pg-body">

  {{-- ── Nav ── --}}
  <nav class="pg-nav">
    <a href="{{ route('home') }}" class="pg-brand">
      @include('components.brand-logo', ['h' => 26])
    </a>
    <div class="pg-nav-links">
      <a href="{{ route('pricing') }}" class="a-link">Pricing</a>
      <a href="{{ route('login') }}" class="a-link">Log in</a>
      <a href="{{ route('register') }}" class="btn-cta">Start free trial</a>
      <button class="pg-theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button">
        <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
        <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
      </button>
    </div>
  </nav>

  {{-- ── Hero ── --}}
  <header class="pg-hero">
    <div class="card-tag"><div class="card-tag-dot"></div> Enterprise</div>
    <h1>Let's build the right plan <em>for your team</em></h1>
    <p>Tell us a little about your business and our team will get back to you within one working day with a tailored quote.</p>
  </header>

  <div class="cs-wrap">
    <div class="cs-grid">

      {{-- Left — value props --}}
      <div>
        <div class="pg-feat-head" style="margin-bottom:4px">What Enterprise adds</div>
        <ul class="cs-points">
          <li>
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            A custom mix of premium modules — pick exactly what your team needs
          </li>
          <li>
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            Volume pricing that scales with unlimited team members
          </li>
          <li>
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m-6 3.75l3 3m0 0l3-3m-3 3V1.5m6 9h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5"/></svg>
            Guided onboarding, data migration and team training
          </li>
          <li>
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            A priority support SLA and a dedicated account manager
          </li>
        </ul>

        @if($salesEmail || $salesPhone || $salesWhatsapp)
        <div class="cs-contact">
          <span>Prefer to reach out directly?</span>
          @if($salesEmail)<a href="mailto:{{ $salesEmail }}">{{ $salesEmail }}</a>@endif
          @if($salesPhone)<a href="tel:{{ preg_replace('/\s+/', '', $salesPhone) }}">{{ $salesPhone }}</a>@endif
          @if($salesWhatsapp)<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $salesWhatsapp) }}" target="_blank" rel="noopener">WhatsApp us</a>@endif
        </div>
        @endif
      </div>

      {{-- Right — form / success --}}
      <div class="cs-card">
        @if(session('sales_sent'))
          <div class="cs-success">
            <div class="cs-check">
              <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h3>Thanks — we've got it</h3>
            <p>Our team will be in touch within one working day. Meanwhile, feel free to <a href="{{ route('register') }}" class="a-link">start a free trial</a>.</p>
          </div>
        @else
          <form method="POST" action="{{ route('contact-sales.store') }}" novalidate>
            @csrf

            <div class="field-grid">
              <div class="field">
                <label class="field-label">Your name *</label>
                <input name="name" type="text" class="field-input @error('name') has-error @enderror" value="{{ old('name') }}" placeholder="Priya Sharma" required/>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
              </div>
              <div class="field">
                <label class="field-label">Company *</label>
                <input name="company" type="text" class="field-input @error('company') has-error @enderror" value="{{ old('company') }}" placeholder="Acme Pvt. Ltd." required/>
                @error('company') <p class="field-error">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="field-grid">
              <div class="field">
                <label class="field-label">Work email *</label>
                <input name="email" type="email" class="field-input @error('email') has-error @enderror" value="{{ old('email') }}" placeholder="priya@acme.com" required/>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
              </div>
              <div class="field">
                <label class="field-label">Phone</label>
                <input name="phone" type="tel" class="field-input @error('phone') has-error @enderror" value="{{ old('phone') }}" placeholder="+91 98765 43210"/>
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="field">
              <label class="field-label">Team size</label>
              <select name="team_size" class="field-input field-select">
                <option value="">Select…</option>
                @foreach(['1–10', '11–25', '26–50', '51–100', '100+'] as $range)
                <option value="{{ $range }}" {{ old('team_size') === $range ? 'selected' : '' }}>{{ $range }}</option>
                @endforeach
              </select>
            </div>

            <div class="field">
              <label class="field-label">How can we help?</label>
              <textarea name="message" class="field-input @error('message') has-error @enderror" placeholder="Tell us about your team, the modules you need, and anything else that matters.">{{ old('message') }}</textarea>
              @error('message') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-cta" style="width:100%;margin-top:6px">Send enquiry</button>
            <p style="font-size:12px;color:var(--text-400);text-align:center;margin:12px 0 0">
              We'll only use your details to respond to this enquiry.
            </p>
          </form>
        @endif
      </div>

    </div>
  </div>

  {{-- ── Footer ── --}}
  <footer class="pg-footer" style="margin-top:64px">
    <div class="pg-footer-copy">© {{ now()->year }} Milan CRM. Built for Indian businesses.</div>
    <div class="pg-footer-links">
      <a href="{{ route('pricing') }}">Pricing</a>
      <a href="{{ route('login') }}">Log in</a>
      <a href="{{ route('register') }}">Get started</a>
      <a href="{{ route('privacy-policy') }}">Privacy</a>
    </div>
  </footer>

</div>

<script>
function toggleTheme() {
  const html = document.documentElement;
  const isD = html.dataset.theme === 'dark';
  html.dataset.theme = isD ? 'light' : 'dark';
  document.getElementById('ico-moon').style.display = isD ? 'none' : '';
  document.getElementById('ico-sun').style.display  = isD ? '' : 'none';
  localStorage.setItem('crm_theme', isD ? 'light' : 'dark');
}
(function() {
  if (document.documentElement.dataset.theme === 'light') {
    document.getElementById('ico-moon').style.display = 'none';
    document.getElementById('ico-sun').style.display  = '';
  }
})();
</script>
</body>
</html>
