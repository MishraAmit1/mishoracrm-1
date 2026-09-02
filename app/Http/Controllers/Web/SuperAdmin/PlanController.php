<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount([
            'subscriptions as active_subs_count' => fn($q) => $q->where('status', 'active'),
        ])->orderBy('sort_order')->get();

        $totalActiveSubs = Subscription::where('status', 'active')->count();
        $monthlyBillingEnabled = PlatformSetting::get('monthly_billing_enabled', '0') === '1';

        return view('superadmin.plans.index', compact('plans', 'totalActiveSubs', 'monthlyBillingEnabled'));
    }

    public function create(): View
    {
        return view('superadmin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);
        $data['features'] = $this->buildFeatures($request);

        Plan::create($data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan "' . $data['name'] . '" created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request, $plan->id);
        $data['features'] = $this->buildFeatures($request);

        $plan->update($data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $activeSubs = $plan->activeSubscriptions()->count();
        if ($activeSubs > 0) {
            return back()->with('error', "Cannot delete — {$activeSubs} active subscription(s) on this plan.");
        }

        $plan->delete();
        return back()->with('success', 'Plan deleted.');
    }

    public function toggle(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', 'Plan ' . ($plan->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function toggleMonthlyBilling(): RedirectResponse
    {
        $enabled = PlatformSetting::get('monthly_billing_enabled', '0') === '1';
        PlatformSetting::set('monthly_billing_enabled', $enabled ? '0' : '1');

        return back()->with('success', 'Monthly billing is now ' . ($enabled ? 'disabled' : 'enabled') . ' on the pricing page.');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function validatePlan(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'required|string|max:50|regex:/^[a-z0-9\-]+$/|unique:plans,slug' . ($ignoreId ? ',' . $ignoreId : '');

        $data = $request->validate([
            'name'                      => 'required|string|max:100',
            'slug'                      => $slugRule,
            'description'               => 'nullable|string|max:255',
            'monthly_price'             => 'required|numeric|min:0',
            'yearly_price'              => 'required|numeric|min:0',
            'discount_percentage'       => 'nullable|integer|min:0|max:100',
            'razorpay_monthly_plan_id'  => 'nullable|string|max:100',
            'razorpay_yearly_plan_id'   => 'nullable|string|max:100',
            'is_active'                 => 'boolean',
            'sort_order'                => 'required|integer|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function buildFeatures(Request $request): array
    {
        $leads = $request->boolean('leads_unlimited') ? -1 : (int) $request->input('leads_count', 0);
        $users = $request->boolean('users_unlimited') ? -1 : (int) $request->input('users_count', 0);

        $features = [
            'leads'        => $leads,
            'users'        => $users,
            'whatsapp'     => $request->boolean('feat_whatsapp'),
            'reports'      => $request->boolean('feat_reports'),
            'social_leads' => $request->boolean('feat_social_leads'),
        ];

        // remove false booleans to keep JSON clean (optional features only when true)
        if (!$features['social_leads']) unset($features['social_leads']);

        // Gated premium modules — single source of truth in config/modules.php.
        // Only truthy keys get written, same "optional features only when true" rule.
        foreach (array_keys(config('modules')) as $key) {
            if ($request->boolean('feat_' . $key)) {
                $features[$key] = true;
            }
        }

        return $features;
    }
}
