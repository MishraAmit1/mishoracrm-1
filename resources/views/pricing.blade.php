<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"/>
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<title>Pricing — Mishora CRM</title>
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
  .pg-plans-wrap { padding-bottom: 0; }
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
      <a href="{{ route('contact-sales') }}" class="a-link">Contact sales</a>
      <a href="{{ route('login') }}" class="a-link">Log in</a>
      <a href="{{ route('register') }}" class="btn-cta">Get started</a>
      <button class="pg-theme-btn" onclick="toggleTheme()" title="Toggle theme" type="button">
        <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
        <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
      </button>
    </div>
  </nav>

  @php $anyTrial = collect($plans)->contains(fn ($p) => (int) $p->trial_days > 0); @endphp

  {{-- ── Hero ── --}}
  <header class="pg-hero">
    <div class="card-tag"><div class="card-tag-dot"></div> Simple, honest pricing</div>
    <h1>Pricing that scales <em>with your business</em></h1>
    <p>{{ $anyTrial ? 'Try it free, no credit card to start.' : 'No credit card to start.' }} No hidden fees, cancel anytime.</p>
  </header>

  @if($monthlyBillingEnabled)
  <div class="pg-billing-toggle">
    <span class="pg-toggle-label active" id="pg-lbl-monthly">Monthly</span>
    <label class="pg-toggle-switch">
      <input type="checkbox" id="pg-billing-toggle">
      <span class="pg-toggle-slider"></span>
    </label>
    <span class="pg-toggle-label" id="pg-lbl-yearly">Yearly <span class="pg-save-badge">Save 20%</span></span>
  </div>
  @else
  <div style="height:44px"></div>
  @endif

  {{-- ── Plans ── --}}
  @php $planCount = count($plans); @endphp
  <div class="pg-plans-wrap" @if($planCount && $planCount <= 2) style="max-width:760px" @elseif($planCount === 3) style="max-width:980px" @elseif($planCount === 4) style="max-width:1180px" @elseif($planCount >= 5) style="max-width:1500px" @endif>
    <div class="pg-plans-grid">
      @forelse($plans as $plan)
      @php
          $isCustom     = $plan->is_custom;
          $isPopular    = !$isCustom && ($plan->slug === 'pro' || ($planCount <= 2 && $loop->last));
          $monthlyPrice = (int) $plan->monthly_price;
          $yearlyPrice  = (int) $plan->yearly_price;
          $hasDiscount  = $plan->hasDiscount();
          $discMonthly  = (int) $plan->discountedMonthlyPrice();
          $discYearly   = (int) $plan->discountedYearlyPrice();
          $users        = $plan->getFeature('users');
          $isFree       = !$isCustom && $monthlyPrice == 0 && $yearlyPrice == 0;
          $trialDays    = (int) $plan->trial_days;
      @endphp
      <div class="pg-plan-card {{ $isPopular ? 'popular' : '' }}">
          @if($isPopular)
          <div class="pg-popular-badge">Most Popular</div>
          @endif

          <div class="pg-plan-name">{{ $plan->name }}</div>
          <div class="pg-plan-desc">{{ $plan->description ?: 'Everything you need to run a growing team on Mishora CRM.' }}</div>

          <div class="pg-plan-price">
            @if($isCustom)
              <span class="pg-price-amount">Custom</span>
            @else
            <div class="pg-monthly-price" @if(!$monthlyBillingEnabled) style="display:none" @endif>
              @if($monthlyPrice == 0)
                <span class="pg-price-amount">Free</span>
              @else
                @if($hasDiscount)<span class="pg-price-original">₹{{ number_format($monthlyPrice) }}</span>@endif
                <span class="pg-price-amount">₹{{ number_format($hasDiscount ? $discMonthly : $monthlyPrice) }}</span>
                <span class="pg-price-period">/month</span>
                @if($hasDiscount)<span class="pg-discount-badge">{{ $plan->discount_percentage }}% off</span>@endif
              @endif
            </div>
            <div class="pg-yearly-price" @if($monthlyBillingEnabled) style="display:none" @endif>
              @if($yearlyPrice == 0)
                <span class="pg-price-amount">Free</span>
              @else
                @if($hasDiscount)<span class="pg-price-original">₹{{ number_format($yearlyPrice) }}</span>@endif
                <span class="pg-price-amount">₹{{ number_format($hasDiscount ? $discYearly : $yearlyPrice) }}</span>
                <span class="pg-price-period">/year</span>
                @if($hasDiscount)<span class="pg-discount-badge">{{ $plan->discount_percentage }}% off</span>@endif
              @endif
            </div>
            @endif
          </div>
          @if($isCustom)
          <div class="pg-price-yearly-note">Volume pricing for large teams</div>
          @elseif($trialDays > 0)
          <div class="pg-price-yearly-note">{{ $trialDays }}-day free trial included</div>
          @elseif($yearlyPrice > 0)
          <div class="pg-price-yearly-note pg-yearly-only" @if($monthlyBillingEnabled) style="display:none" @endif>
            ≈ ₹{{ number_format(($hasDiscount ? $discYearly : $yearlyPrice) / 12, 0) }}/mo, billed annually
          </div>
          @endif

          @if($isCustom)
          <a href="{{ route('contact-sales') }}" class="pg-plan-cta btn-ghost">Talk to sales</a>
          @else
          <a href="{{ route('register') }}" class="pg-plan-cta {{ $isPopular ? 'btn-cta' : 'btn-ghost' }}">
            @if($trialDays > 0)
              Start {{ $trialDays }}-day trial
            @elseif($isFree)
              Start free
            @else
              Get started
            @endif
          </a>
          @endif

          <div class="pg-feat-head">What's included</div>
          <ul class="pg-plan-features">
            <li>
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              {{ $users == -1 ? 'Unlimited team members' : 'Up to ' . number_format($users) . ' team members' }}
            </li>
            <li class="{{ $plan->hasFeature('whatsapp') ? '' : 'disabled' }}">
              @if($plan->hasFeature('whatsapp'))
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              @else
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              @endif
              WhatsApp campaigns
            </li>
            <li class="{{ $plan->hasFeature('reports') ? '' : 'disabled' }}">
              @if($plan->hasFeature('reports'))
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              @else
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              @endif
              Advanced reports &amp; analytics
            </li>
            <li class="{{ $plan->hasFeature('social_leads') ? '' : 'disabled' }}">
              @if($plan->hasFeature('social_leads'))
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              @else
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              @endif
              Meta &amp; social lead capture
            </li>
            <li class="{{ $plan->hasFeature('lead_integrations') ? '' : 'disabled' }}">
              @if($plan->hasFeature('lead_integrations'))
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              @else
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              @endif
              Third-party lead sources (IndiaMART, JustDial &amp; more)
            </li>
            @foreach(config('modules') as $modKey => $mod)
              @if($plan->hasFeature($modKey))
              <li>
                <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ $mod['blurb'] }}
              </li>
              @endif
            @endforeach
            @if($isCustom)
            <li>
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              Guided onboarding &amp; data migration
            </li>
            <li>
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              Dedicated account manager &amp; priority SLA
            </li>
            @else
            <li>
              <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
              @if($plan->slug === 'business')
                Priority support &amp; onboarding call
              @elseif($isPopular)
                Priority support
              @else
                Email support
              @endif
            </li>
            @endif
          </ul>
      </div>
      @empty
      <div style="grid-column:1/-1;text-align:center;padding:56px;color:var(--text-300);font-size:14px;">
          Pricing will be available shortly.
      </div>
      @endforelse
    </div>

    {{-- All plans include --}}
    <div class="pg-includes">
      <div class="pg-includes-t">Every plan includes</div>
      <ul class="pg-includes-grid">
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $anyTrial ? 'Free trial — no card needed' : 'No credit card to start' }}</li>
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>GST invoicing &amp; quotations</li>
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Deal pipeline &amp; tasks</li>
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Staff &amp; attendance tracking</li>
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Mobile-ready workspace</li>
        <li><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Bank-grade encryption</li>
      </ul>
    </div>
  </div>

  {{-- ── FAQ ── --}}
  <section class="pg-section">
    <div class="pg-section-head">
      <h2>Questions, answered</h2>
      <p>Everything you need to know before getting started.</p>
    </div>
    <div class="pg-faq">
      <details class="pg-faq-item">
        <summary>Do I need a credit card to start?</summary>
        <p>No. Plans that include a free trial give you full access with no payment details up front — we'll remind you before it ends, and you only pay if you continue. Plans without a trial simply take you to payment after you create your workspace.</p>
      </details>
      <details class="pg-faq-item">
        <summary>Can I change plans later?</summary>
        <p>Yes, any time. Upgrade instantly to add team members and unlock more modules — or downgrade at the end of your billing cycle. Your data always stays intact.</p>
      </details>
      <details class="pg-faq-item">
        <summary>What's included in the Enterprise plan?</summary>
        <p>Enterprise is for larger teams that need more than the standard plans — a custom mix of modules, volume pricing, guided onboarding and data migration, a priority support SLA and a dedicated account manager. <a href="{{ route('contact-sales') }}">Talk to our team</a> and we'll put together a quote.</p>
      </details>
      <details class="pg-faq-item">
        <summary>What happens to my data if I cancel?</summary>
        <p>Your workspace stays available in read-only mode for 30 days after cancellation so you can export everything. You can also request a full data export at any time from Settings.</p>
      </details>
      <details class="pg-faq-item">
        <summary>Is WhatsApp messaging really included?</summary>
        <p>Yes — plans marked with WhatsApp campaigns include template messaging, bulk sends and delivery tracking through the official WhatsApp Business API. Standard Meta conversation charges may apply.</p>
      </details>
      <details class="pg-faq-item">
        <summary>Do you offer support during setup?</summary>
        <p>Every plan includes email support, and the Most Popular plan adds priority support with faster response times. Onboarding help and data migration assistance are available on request.</p>
      </details>
    </div>
  </section>

  {{-- ── CTA band ── --}}
  <section class="pg-cta-band">
    <div class="pg-cta-inner">
      <h2>Start growing with Mishora CRM today</h2>
      <p>Set up your workspace in minutes. No credit card, no commitment.</p>
      <a href="{{ route('register') }}" class="pg-cta-btn">
        Create your workspace
        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
      </a>
    </div>
  </section>

  {{-- ── Footer ── --}}
  <footer class="pg-footer">
    <div class="pg-footer-copy">© {{ now()->year }} Mishora CRM. Built for Indian businesses.</div>
    <div class="pg-footer-links">
      <a href="{{ route('contact-sales') }}">Contact sales</a>
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
  document.getElementById('ico-moon').style.display = isD ? '' : 'none';
  document.getElementById('ico-sun').style.display  = isD ? 'none' : '';
  localStorage.setItem('crm_theme', isD ? 'light' : 'dark');
}
(function() {
  if (document.documentElement.dataset.theme === 'light') {
    document.getElementById('ico-moon').style.display = '';
    document.getElementById('ico-sun').style.display  = 'none';
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
