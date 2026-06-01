<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()?->tenant?->settings['theme'] ?? 'dark' }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Subscription Expired — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: var(--bg-app); }
        .expired-box {
            max-width: 520px;
            width: 100%;
            padding: 48px 32px;
            text-align: center;
        }
        .expired-icon {
            width: 80px; height: 80px;
            background: rgba(239,68,68,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            color: #ef4444;
        }
        h1 { font-size: 26px; font-weight: 800; color: var(--text-100); margin-bottom: 8px; }
        p { font-size: 14.5px; color: var(--text-400); line-height: 1.6; margin-bottom: 32px; }
        .plans-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
            text-align: left;
        }
        .plan-mini-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--r-lg);
            padding: 16px;
        }
        .plan-mini-name { font-size: 14px; font-weight: 700; color: var(--text-100); margin-bottom: 4px; }
        .plan-mini-price { font-size: 20px; font-weight: 800; color: var(--accent); }
        .plan-mini-period { font-size: 12px; color: var(--text-400); }
        .plan-mini-btn {
            display: block;
            margin-top: 12px;
            padding: 8px;
            background: var(--accent);
            color: #fff;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .plan-mini-btn:hover { opacity: 0.85; }
        .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-400);
            text-decoration: none;
            cursor: pointer;
        }
        .logout-link:hover { color: var(--text-200); }
    </style>
</head>
<body>
<div class="expired-box">

    <div class="expired-icon">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
    </div>

    <h1>Subscription Expired</h1>
    <p>
        Aapka subscription khatam ho gaya hai. Apna CRM access waapis paane ke liye neeche se koi ek plan select karein.
    </p>

    @if($plans->count())
    <div class="plans-mini">
        @foreach($plans->where('monthly_price', '>', 0) as $plan)
        <div class="plan-mini-card">
            <div class="plan-mini-name">{{ $plan->name }}</div>
            <div class="plan-mini-price">₹{{ number_format($plan->monthly_price) }}</div>
            <div class="plan-mini-period">/month</div>
            <a href="{{ route('tenant.subscription.checkout', [$plan->slug, 'monthly']) }}" class="plan-mini-btn">Subscribe</a>
        </div>
        @endforeach
    </div>
    @endif

    <form action="{{ route('logout') }}" method="POST" style="display:inline">
        @csrf
        <button type="submit" class="logout-link">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
            Sign out from this account
        </button>
    </form>

</div>
</body>
</html>
