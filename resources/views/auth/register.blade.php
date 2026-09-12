<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"/>
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<title>Create workspace — Mishora CRM</title>
<script>
(function () {
  var saved = localStorage.getItem('crm_theme');
  if (saved) document.documentElement.dataset.theme = saved;
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) ?: '1' }}"/>
<style>
/* Register-specific: the wizard card is a touch wider (fits the plan grid on step 3) */
.auth-card { max-width: 520px; }
</style>
</head>
<body>
@php $anyTrial = collect($plans)->contains(fn ($p) => (int) $p->trial_days > 0); @endphp

<div class="auth-shell">

  <div class="auth-form-panel">

    <div class="top-actions">
      <a href="{{ route('pricing') }}" class="a-link">Pricing</a>
      <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button">
        <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
        <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
      </button>
    </div>

    <div class="auth-card">

      <a href="{{ route('home') }}" class="center-logo">
        @include('components.brand-logo', ['h' => 28])
      </a>

      <div class="card-head" style="text-align:center">
        <div class="card-tag"><div class="card-tag-dot"></div> {{ $anyTrial ? 'Free trial included' : 'Set up in minutes' }}</div>
        <h1>Create workspace</h1>
        <p>Step <span id="stepNum">1</span> of 3 — <span id="stepDesc">Company details</span></p>
      </div>

      {{-- Step progress --}}
      <div class="steps-nav-wrap">
        <div class="steps-nav" id="stepsNav">
          <div class="step-item is-active" id="si-1">
            <div class="step-num">1</div>
            <div class="step-line"></div>
          </div>
          <div class="step-item" id="si-2">
            <div class="step-num">2</div>
            <div class="step-line"></div>
          </div>
          <div class="step-item" id="si-3">
            <div class="step-num">3</div>
          </div>
        </div>
        <div class="steps-labels">
          <span id="sl-1" class="is-active">Company</span>
          <span id="sl-2">Account</span>
          <span id="sl-3">Plan</span>
        </div>
      </div>

      @if ($errors->any())
      <div class="alert alert-error">
        <span class="alert-icon">⚠</span>
        <span>{{ $errors->first() }}</span>
      </div>
      @endif

      <form method="POST" action="{{ route('register.store') }}" id="regForm" novalidate>
        @csrf

        {{-- ── Step 1: Company ── --}}
        <div class="step-panel is-active" id="sp-1">

          <div class="field">
            <label class="field-label">Company / Business name *</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
              </span>
              <input name="company_name" type="text" class="field-input @error('company_name') has-error @enderror" placeholder="Acme Pvt. Ltd." value="{{ old('company_name') }}" required/>
            </div>
            @error('company_name') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="field">
            <label class="field-label">Workspace URL *</label>
            <div class="subdomain-row">
              <span class="subdomain-pre">https://</span>
              <input name="subdomain" type="text" class="subdomain-input @error('subdomain') has-error @enderror" placeholder="yourcompany" id="subInput" value="{{ old('subdomain') }}" required/>
              <span class="subdomain-suf">.mishoracrm.in</span>
            </div>
            @error('subdomain') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="field-grid" style="margin-bottom:16px">
            <div class="field">
              <label class="field-label">Industry</label>
              <div class="field-wrap">
                <span class="field-icon">
                  <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </span>
                <select name="industry" class="field-input field-select">
                  <option value="">Select...</option>
                  <option {{ old('industry')=='real_estate'?'selected':'' }} value="real_estate">Real Estate</option>
                  <option {{ old('industry')=='retail'?'selected':'' }} value="retail">Retail</option>
                  <option {{ old('industry')=='manufacturing'?'selected':'' }} value="manufacturing">Manufacturing</option>
                  <option {{ old('industry')=='education'?'selected':'' }} value="education">Education</option>
                  <option {{ old('industry')=='healthcare'?'selected':'' }} value="healthcare">Healthcare</option>
                  <option {{ old('industry')=='finance'?'selected':'' }} value="finance">Finance & BFSI</option>
                  <option {{ old('industry')=='it'?'selected':'' }} value="it">IT / SaaS</option>
                  <option {{ old('industry')=='other'?'selected':'' }} value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="field">
              <label class="field-label">Team size</label>
              <div class="field-wrap">
                <span class="field-icon">
                  <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </span>
                <select name="team_size" class="field-input field-select">
                  <option value="">Select...</option>
                  <option>1–5</option>
                  <option>6–20</option>
                  <option>21–50</option>
                  <option>50+</option>
                </select>
              </div>
            </div>
          </div>

          <div class="field">
            <label class="field-label">Business phone</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
              </span>
              <input name="phone" type="tel" class="field-input" placeholder="+91 98765 43210" value="{{ old('phone') }}"/>
            </div>
          </div>

          <div style="margin-top:20px">
            <button type="button" class="btn-cta" onclick="goStep(2)">Continue →</button>
          </div>
        </div>

        {{-- ── Step 2: Account ── --}}
        <div class="step-panel" id="sp-2">

          <div class="field-grid" style="margin-bottom:16px">
            <div class="field">
              <label class="field-label">First name *</label>
              <div class="field-wrap">
                <span class="field-icon"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg></span>
                <input name="first_name" type="text" class="field-input @error('first_name') has-error @enderror" placeholder="Rahul" value="{{ old('first_name') }}" required/>
              </div>
              @error('first_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
              <label class="field-label">Last name *</label>
              <div class="field-wrap">
                <span class="field-icon"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg></span>
                <input name="last_name" type="text" class="field-input @error('last_name') has-error @enderror" placeholder="Sharma" value="{{ old('last_name') }}" required/>
              </div>
              @error('last_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
          </div>

          <div class="field">
            <label class="field-label">Work email *</label>
            <div class="field-wrap">
              <span class="field-icon"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg></span>
              <input name="email" type="email" class="field-input @error('email') has-error @enderror" placeholder="rahul@company.com" value="{{ old('email') }}" required/>
            </div>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="field">
            <label class="field-label">Password *</label>
            <div class="field-wrap">
              <span class="field-icon"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
              <input name="password" id="pwdInput" type="password" class="field-input @error('password') has-error @enderror" placeholder="Min. 8 characters" oninput="checkPwd(this.value)" required/>
              <button type="button" class="eye-btn" onclick="toggleEye('pwdInput',this)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </button>
            </div>
            <div class="strength-row">
              <div class="strength-bar" id="sb1"></div>
              <div class="strength-bar" id="sb2"></div>
              <div class="strength-bar" id="sb3"></div>
              <div class="strength-bar" id="sb4"></div>
            </div>
            <p class="strength-text" id="pwdStrText">Use 8+ chars with uppercase & numbers</p>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="field">
            <label class="field-label">Confirm password *</label>
            <div class="field-wrap">
              <span class="field-icon"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
              <input name="password_confirmation" type="password" class="field-input" placeholder="Repeat password" required/>
            </div>
          </div>

          <div class="btn-row" style="margin-top:20px">
            <button type="button" class="btn-ghost" onclick="goStep(1)">← Back</button>
            <button type="button" class="btn-cta" onclick="goStep(3)">Continue →</button>
          </div>
        </div>

        {{-- ── Step 3: Plan ── --}}
        <div class="step-panel" id="sp-3">

          <div class="plans-grid">
            @php $moduleCount = count(config('modules')); @endphp
            @foreach($plans as $plan)
            @php
                $pUsers   = $plan->getFeature('users');
                $pModules = collect(array_keys(config('modules')))->filter(fn($k) => $plan->hasFeature($k))->count();
                $pMonthly = (int) $plan->monthly_price;
                $pTrial   = (int) $plan->trial_days;
                $feats    = [
                    $pUsers == -1 ? 'Unlimited users' : $pUsers . ' users',
                    $plan->hasFeature('whatsapp') ? 'WhatsApp campaigns' : 'Core CRM',
                    $pModules === 0
                        ? 'Leads, deals & invoicing'
                        : ($pModules >= $moduleCount ? 'All premium modules' : $pModules . ' add-on modules'),
                    $pTrial > 0 ? $pTrial . '-day free trial' : ($pMonthly === 0 ? 'Free forever' : 'Pay to start'),
                ];
            @endphp
            <label class="plan-opt {{ $loop->first ? 'is-selected' : '' }}" onclick="selectPlan(this)" style="position:relative;">
              @if($plan->slug === 'pro')<div class="plan-badge">POPULAR</div>@endif
              <input type="radio" name="plan" value="{{ $plan->slug }}" style="display:none" {{ $loop->first ? 'checked' : '' }}/>
              <div class="plan-name">{{ $plan->name }}</div>
              <div class="plan-price">
                @if($pMonthly === 0)Free @else ₹{{ number_format($pMonthly) }}<sub>/mo</sub> @endif
              </div>
              <div class="plan-feats">{!! implode('<br/>', $feats) !!}</div>
            </label>
            @endforeach
          </div>

          <div class="terms-box" style="margin-bottom:16px">
            <input type="checkbox" name="terms" id="termsChk" required/>
            <p>I agree to Mishora CRM's <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a>. Paid plans are billed after any free-trial period ends.</p>
          </div>

          <div class="btn-row">
            <button type="button" class="btn-ghost" onclick="goStep(2)">← Back</button>
            <button type="submit" class="btn-cta" id="submitBtn">Create workspace</button>
          </div>
        </div>

      </form>

      <div class="form-foot">
        Already have a workspace? <a href="{{ route('login') }}" class="a-link">Sign in →</a>
      </div>

    </div>

    <div class="center-foot">
      <span>© {{ now()->year }} Mishora CRM</span>
      <span>·</span>
      <a href="/terms">Terms</a>
      <span>·</span>
      <a href="/privacy">Privacy</a>
    </div>

  </div>

</div>

<script>
// Theme
function toggleTheme() {
  const html = document.documentElement;
  const isD = html.dataset.theme === 'dark';
  html.dataset.theme = isD ? 'light' : 'dark';
  document.getElementById('ico-moon').style.display = isD ? '' : 'none';
  document.getElementById('ico-sun').style.display  = isD ? 'none' : '';
  localStorage.setItem('crm_theme', isD ? 'light' : 'dark');
}
(function() {
  // Theme itself is already applied in <head> to avoid a flash; just sync the icon.
  if (document.documentElement.dataset.theme === 'light') { document.getElementById('ico-moon').style.display=''; document.getElementById('ico-sun').style.display='none'; }
})();

// Steps
let cur = 1;
const stepDescs = ['', 'Company details', 'Your account', 'Choose a plan'];
function goStep(n) {
  document.getElementById('sp-' + cur).classList.remove('is-active');
  document.getElementById('sp-' + n).classList.add('is-active');
  for (let i = 1; i <= 3; i++) {
    const si = document.getElementById('si-' + i);
    const sl = document.getElementById('sl-' + i);
    const dot = si.querySelector('.step-num');
    si.classList.remove('is-active','is-done');
    sl.classList.remove('is-active','is-done');
    if (i < n) { si.classList.add('is-done'); sl.classList.add('is-done'); dot.textContent = '✓'; }
    else if (i === n) { si.classList.add('is-active'); sl.classList.add('is-active'); dot.textContent = i; }
    else { dot.textContent = i; }
  }
  cur = n;
  document.getElementById('stepNum').textContent = n;
  document.getElementById('stepDesc').textContent = stepDescs[n];
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Subdomain
document.getElementById('subInput').addEventListener('input', function() {
  this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g,'');
});

// Password strength
function checkPwd(v) {
  const checks = [v.length >= 8, /[A-Z]/.test(v), /[0-9]/.test(v), /[^A-Za-z0-9]/.test(v)];
  const score = checks.filter(Boolean).length;
  const colors = ['','#ff5257','#f8b84e','#6378ff','#2dd4a0'];
  const labels = ['','Weak — add more characters','Fair — add uppercase or numbers','Good — add a symbol','Strong password ✓'];
  for (let i = 1; i <= 4; i++) {
    document.getElementById('sb'+i).style.background = i <= score ? colors[score] : 'var(--border-default)';
  }
  const txt = document.getElementById('pwdStrText');
  txt.textContent = v ? labels[score] : 'Use 8+ chars with uppercase & numbers';
  txt.style.color = v ? (colors[score] || 'var(--text-300)') : 'var(--text-300)';
}

// Plan select
function selectPlan(el) {
  document.querySelectorAll('.plan-opt').forEach(x => x.classList.remove('is-selected'));
  el.classList.add('is-selected');
  const radio = el.querySelector('input[type=radio]');
  if (radio) radio.checked = true;
}

// Eye toggle
function toggleEye(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.querySelector('svg').style.opacity = inp.type === 'text' ? '0.45' : '1';
}

// Submit
document.getElementById('regForm').addEventListener('submit', function(e) {
  if (!document.getElementById('termsChk').checked) {
    e.preventDefault();
    alert('Please accept the Terms of Service to continue.');
    return;
  }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<span class="spin"></span>Creating workspace...';
  btn.disabled = true;
});
</script>
</body>
</html>