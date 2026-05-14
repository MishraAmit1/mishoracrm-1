<?php

namespace App\Http\Controllers\Web\Auth;
 
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
 
class RegisterController extends Controller
{
    // ── Show register form ────────────────────────────────────────
    public function show(): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('auth.register', compact('plans'));
    }
 
    // ── Handle registration ───────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain'    => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:tenants,subdomain'],
            'industry'     => ['nullable', 'string', 'max:100'],
            'team_size'    => ['nullable', 'string'],
            'phone'        => ['nullable', 'string', 'max:15'],
            'gst'          => ['nullable', 'string', 'max:20'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'plan'         => ['nullable', 'string', 'exists:plans,slug'],
            'terms'        => ['accepted'],
        ], [
            'subdomain.alpha_dash' => 'Subdomain can only contain letters, numbers, and hyphens.',
            'subdomain.unique'     => 'This subdomain is already taken. Please choose another.',
            'email.unique'         => 'This email is already registered.',
            'terms.accepted'       => 'You must accept the terms and conditions.',
        ]);
 
        try {
            $result = DB::transaction(function () use ($request) {
 
                // 1. Create tenant
                $tenant = Tenant::create([
                    'name'      => $request->company_name,
                    'subdomain' => strtolower($request->subdomain),
                    'email'     => $request->email,
                    'phone'     => $request->phone,
                    'status'    => 'active',
                    'settings'  => [
                        'industry'  => $request->industry,
                        'team_size' => $request->team_size,
                        'gst'       => $request->gst,
                    ],
                ]);
 
                // 2. Create admin user
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name'      => trim($request->first_name . ' ' . $request->last_name),
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'phone'     => $request->phone,
                    'user_type' => 'tenant_admin',
                    'is_active' => true,
                ]);
 
                // 3. Assign role
                $user->assignRole('tenant_admin');
 
                // 4. Assign plan / subscription
                $planSlug = $request->plan ?? 'free';
                $plan     = Plan::where('slug', $planSlug)->first()
                         ?? Plan::where('slug', 'free')->first();
 
                if ($plan) {
                    $tenant->subscriptions()->create([
                        'plan_id'        => $plan->id,
                        'status'         => 'trial',
                        'billing_cycle'  => 'monthly',
                        'trial_ends_at'  => now()->addDays(14),
                        'started_at'     => now(),
                        'ends_at'        => now()->addDays(14),
                    ]);
                }
 
                return compact('tenant', 'user');
            });
 
            // Auto-login after registration
            Auth::login($result['user']);
 
            return redirect()->route('dashboard')
                ->with('success', 'Welcome! Your workspace is ready. 🎉');
 
        } catch (Exception $ex) {
            return back()
                ->withInput()
                ->with('error', "Something went wrong. Please try again. $ex");
        }
    }
}