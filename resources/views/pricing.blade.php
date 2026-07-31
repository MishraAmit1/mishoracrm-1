<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Pricing — CrmPro</title>
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
body { overflow-x: hidden; }

/* ── Top nav ── */
.pg-nav {
  display: flex; align-items: center; justify-content: space-between;
  max-width: 1140px; margin: 0 auto;
  padding: 24px 24px 0;
}
.pg-nav-links { display: flex; align-items: center; gap: 10px; }
.pg-nav-links a.a-link { padding: 9px 6px; }
.pg-nav-links a.btn-ghost { width: auto; padding: 9px 18px; text-decoration: none; }
.pg-nav-links a.btn-cta { width: auto; padding: 9px 18px; text-decoration: none; display: inline-flex; align-items: center; }
.pg-theme-btn {
  width: 36px; height: 36px; margin-left: 4px;
  background: var(--bg-elevated); border: 1px solid var(--border-default); border-radius: var(--r-sm);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: var(--text-200); transition: border-color var(--t-fast) var(--ease), color var(--t-fast) var(--ease);
}
.pg-theme-btn:hover { border-color: var(--border-strong); color: var(--text-100); }
.pg-theme-btn svg { width: 16px; height: 16px; }

/* ── Hero ── */
.pg-hero { max-width: 720px; margin: 0 auto; padding: 64px 24px 8px; text-align: center; }
.pg-hero .card-tag { margin: 0 auto 18px; }
.pg-hero h1 {
  font-size: clamp(30px, 4.5vw, 44px); font-weight: 800; letter-spacing: -1.2px;
  color: var(--text-100); line-height: 1.12; margin-bottom: 14px;
}
.pg-hero h1 em { font-style: normal; color: var(--accent); }
.pg-hero p { font-size: 15.5px; color: var(--text-200); line-height: 1.6; max-width: 520px; margin: 0 auto; }

/* ── Billing toggle ── */
.pg-billing-toggle {
  display: flex; align-items: center; justify-content: center; gap: 14px;
  margin: 36px 0 44px;
}
.pg-toggle-label { font-size: 14px; font-weight: 600; color: var(--text-300); cursor: pointer; transition: color var(--t-fast) var(--ease); }
.pg-toggle-label.active { color: var(--text-100); }
.pg-toggle-switch { position: relative; width: 46px; height: 26px; flex-shrink: 0; }
.pg-toggle-switch input { opacity: 0; width: 0; height: 0; }
.pg-toggle-slider {
  position: absolute; inset: 0;
  background: var(--bg-input); border: 1px solid var(--border-default);
  border-radius: 100px; cursor: pointer; transition: 0.2s;
}
.pg-toggle-slider:before {
  content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px;
  background: var(--accent); border-radius: 50%; transition: 0.2s;
  box-shadow: 0 0 8px var(--accent-glow);
}
.pg-toggle-switch input:checked + .pg-toggle-slider:before { transform: translateX(20px); }
.pg-save-badge {
  background: var(--green); color: #04231a; font-size: 11px; font-weight: 700;
  padding: 2px 9px; border-radius: 100px; margin-left: 6px; letter-spacing: 0.2px;
}

/* ── Plans grid ── */
.pg-plans-wrap { max-width: 1140px; margin: 0 auto; padding: 0 24px 80px; }
.pg-plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; align-items: stretch; }
.pg-plan-card {
  background: var(--bg-surface); border: 1.5px solid var(--border-default);
  border-radius: var(--r-lg); padding: 30px 26px;
  display: flex; flex-direction: column;
  position: relative; transition: border-color var(--t-fast) var(--ease), transform var(--t-fast) var(--ease);
}
.pg-plan-card:hover { transform: translateY(-3px); border-color: var(--border-strong); }
.pg-plan-card.popular { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent), 0 12px 32px rgba(99,120,255,0.15); }
.pg-popular-badge {
  position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
  background: var(--accent); color: #fff; font-size: 11px; font-weight: 700;
  padding: 4px 16px; border-radius: 100px; white-space: nowrap; letter-spacing: 0.2px;
}
.pg-plan-name { font-size: 17px; font-weight: 700; color: var(--text-100); margin-bottom: 6px; }
.pg-plan-desc { font-size: 13px; color: var(--text-300); margin-bottom: 22px; min-height: 18px; }
.pg-plan-price { margin-bottom: 26px; }
.pg-price-amount { font-size: 38px; font-weight: 800; color: var(--text-100); line-height: 1; font-family: var(--mono); letter-spacing: -1px; }
.pg-price-original { font-size: 17px; font-weight: 500; color: var(--text-300); text-decoration: line-through; margin-right: 6px; font-family: var(--mono); }
.pg-price-period { font-size: 13px; color: var(--text-300); margin-left: 4px; }
.pg-price-yearly-note { font-size: 12px; color: var(--text-300); margin-top: 6px; }
.pg-discount-badge {
  display: inline-block; background: rgba(45,212,160,0.12); color: var(--green);
  font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 100px; margin-left: 6px; vertical-align: middle;
}
.pg-plan-features { list-style: none; padding: 0; margin: 0 0 26px; display: flex; flex-direction: column; gap: 11px; flex: 1; }
.pg-plan-features li { display: flex; align-items: center; gap: 9px; font-size: 13.5px; color: var(--text-200); }
.pg-plan-features li .feat-icon { width: 17px; height: 17px; flex-shrink: 0; color: var(--accent); }
.pg-plan-features li.disabled { color: var(--text-400); text-decoration: line-through; }
.pg-plan-features li.disabled .feat-icon { color: var(--text-400); }

/* ── Footer note ── */
.pg-footer-note { text-align: center; margin-top: 8px; font-size: 13px; color: var(--text-300); }
.pg-footer-note a { color: var(--accent); text-decoration: none; font-weight: 500; }
.pg-footer-note a:hover { text-decoration: underline; }

@media (max-width: 640px) {
  .pg-nav { flex-direction: column; align-items: flex-start; gap: 14px; }
  .pg-nav-links { width: 100%; justify-content: flex-end; }
}
</style>
</head>
<body>

<nav class="pg-nav">
  <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
    <div class="v-logo-icon" style="width:32px;height:32px;">
      <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
    </div>
    <span class="v-logo-name" style="font-size:16px;">Crm<span>Pro</span></span>
  </a>
  <div class="pg-nav-links">
    <a href="{{ route('login') }}" class="a-link">Log in</a>
    <a href="{{ route('register') }}" class="btn-cta">Get Started</a>
    <button class="pg-theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button">
      <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
      <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
    </button>
  </div>
</nav>

<div class="pg-hero">
  <div class="card-tag"><div class="card-tag-dot"></div> Simple pricing</div>
  <h1>Plans that grow <em>with your business</em></h1>
  <p>No hidden fees. Cancel anytime. Every plan starts with a 14-day free trial — no credit card required.</p>
</div>

@if($monthlyBillingEnabled)
<div class="pg-billing-toggle">
  <span class="pg-toggle-label active" id="pg-lbl-monthly">Monthly</span>
  <label class="pg-toggle-switch">
    <input type="checkbox" id="pg-billing-toggle">
    <span class="pg-toggle-slider"></span>
  </label>
  <span class="pg-toggle-label" id="pg-lbl-yearly">Yearly <span class="pg-save-badge">Save 20%</span></span>
</div>
@endif

<div class="pg-plans-wrap">
  <div class="pg-plans-grid">
    @forelse($plans as $plan)
    @php
        $isPopular    = $plan->slug === 'starter';
        $monthlyPrice = (int) $plan->monthly_price;
        $yearlyPrice  = (int) $plan->yearly_price;
        $hasDiscount  = $plan->hasDiscount();
        $discMonthly  = (int) $plan->discountedMonthlyPrice();
        $discYearly   = (int) $plan->discountedYearlyPrice();
        $leads        = $plan->getFeature('leads');
        $users        = $plan->getFeature('users');
    @endphp
    <div class="pg-plan-card {{ $isPopular ? 'popular' : '' }}">
        @if($isPopular)
        <div class="pg-popular-badge">Most Popular</div>
        @endif

        <div class="pg-plan-name">{{ $plan->name }}</div>
        <div class="pg-plan-desc">{{ $plan->description ?? '' }}</div>

        <div class="pg-plan-price">
            <div class="pg-monthly-price" @if(!$monthlyBillingEnabled) style="display:none" @endif>
                @if($monthlyPrice == 0)
                    <span class="pg-price-amount">Free</span>
                @else
                    @if($hasDiscount)
                        <span class="pg-price-original">₹{{ number_format($monthlyPrice) }}</span>
                    @endif
                    <span class="pg-price-amount">₹{{ number_format($hasDiscount ? $discMonthly : $monthlyPrice) }}</span>
                    <span class="pg-price-period">/month</span>
                    @if($hasDiscount)
                        <span class="pg-discount-badge">{{ $plan->discount_percentage }}% OFF</span>
                    @endif
                @endif
            </div>
            <div class="pg-yearly-price" @if($monthlyBillingEnabled) style="display:none" @endif>
                @if($yearlyPrice == 0)
                    <span class="pg-price-amount">Free</span>
                @else
                    @if($hasDiscount)
                        <span class="pg-price-original">₹{{ number_format($yearlyPrice) }}</span>
                    @endif
                    <span class="pg-price-amount">₹{{ number_format($hasDiscount ? $discYearly : $yearlyPrice) }}</span>
                    <span class="pg-price-period">/year</span>
                    @if($hasDiscount)
                        <span class="pg-discount-badge">{{ $plan->discount_percentage }}% OFF</span>
                    @endif
                @endif
            </div>
            @if($yearlyPrice > 0)
            <div class="pg-price-yearly-note pg-yearly-only" @if($monthlyBillingEnabled) style="display:none" @endif>
                (₹{{ number_format(($hasDiscount ? $discYearly : $yearlyPrice) / 12, 0) }}/month billed annually)
            </div>
            @endif
        </div>

        <ul class="pg-plan-features">
            <li>
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $leads == -1 ? 'Unlimited Leads' : number_format($leads) . ' Leads' }}
            </li>
            <li>
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $users == -1 ? 'Unlimited Users' : number_format($users) . ' Team Members' }}
            </li>
            <li class="{{ $plan->hasFeature('whatsapp') ? '' : 'disabled' }}">
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                WhatsApp Integration
            </li>
            <li class="{{ $plan->hasFeature('reports') ? '' : 'disabled' }}">
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Advanced Reports
            </li>
            @if($plan->hasFeature('social_leads'))
            <li>
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Social Media Leads
            </li>
            @endif
        </ul>

        <a href="{{ route('register') }}" class="{{ $isPopular ? 'btn-cta' : 'btn-ghost' }}" style="text-decoration:none;">
            {{ $monthlyPrice == 0 ? 'Start Free' : 'Get Started' }}
        </a>
    </div>
    @empty
    <div style="grid-column:1/-1;text-align:center;padding:56px;color:var(--text-300);font-size:14px;">
        Pricing will be available shortly.
    </div>
    @endforelse
  </div>

  <div class="pg-footer-note">
    Already have a workspace? <a href="{{ route('login') }}">Log in →</a>
  </div>
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

@if($monthlyBillingEnabled)
const pgToggle = document.getElementById('pg-billing-toggle');
const pgLblM   = document.getElementById('pg-lbl-monthly');
const pgLblY   = document.getElementById('pg-lbl-yearly');

function pgSwitchBilling(yearly) {
    pgLblM.classList.toggle('active', !yearly);
    pgLblY.classList.toggle('active', yearly);
    document.querySelectorAll('.pg-monthly-price').forEach(el => el.style.display = yearly ? 'none' : '');
    document.querySelectorAll('.pg-yearly-price, .pg-yearly-only').forEach(el => el.style.display = yearly ? '' : 'none');
}
pgToggle.addEventListener('change', () => pgSwitchBilling(pgToggle.checked));
@endif
</script>
</body>
</html>
