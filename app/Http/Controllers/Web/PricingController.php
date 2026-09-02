<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $monthlyBillingEnabled = PlatformSetting::get('monthly_billing_enabled', '0') === '1';
        $trialEnabled = PlatformSetting::get('trial_enabled', '0') === '1';
        $trialDays    = (int) PlatformSetting::get('trial_days', 14);

        return view('pricing', compact('plans', 'monthlyBillingEnabled', 'trialEnabled', 'trialDays'));
    }
}
