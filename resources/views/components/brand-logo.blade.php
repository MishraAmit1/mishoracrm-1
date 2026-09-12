{{--
    Mishora CRM logo — theme-aware wordmark.
    ======================================
    Renders the full-colour wordmark on light surfaces and the reversed
    (white "MISHORA") wordmark on dark surfaces. The square mark is also
    included so contexts like the collapsed sidebar can swap to it in CSS.

    Usage:  @include('components.brand-logo', ['h' => 26])
      h  — rendered wordmark height in px (default 28)
--}}
@php($h = $h ?? 28)
<span class="brand-logo" style="--brand-logo-h:{{ $h }}px">
    <img src="{{ asset('logo/logo.png') }}" alt="Mishora CRM" class="brand-logo__wm brand-logo__wm--light" width="900" height="185">
    <img src="{{ asset('logo/logo-dark.png') }}" alt="Mishora CRM" class="brand-logo__wm brand-logo__wm--dark" width="900" height="185">
    <img src="{{ asset('logo/logo-mark.png') }}" alt="" aria-hidden="true" class="brand-logo__mark" width="256" height="256">
</span>
