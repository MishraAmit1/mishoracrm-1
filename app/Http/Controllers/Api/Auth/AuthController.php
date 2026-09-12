<?php

namespace App\Http\Controllers\Api\Auth;
 
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
 
class AuthController extends Controller
{
    // ── Register ──────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain'    => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:tenants,subdomain'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'confirmed', PasswordRule::min(8)],
            'phone'        => ['nullable', 'string', 'max:15'],
            'plan'         => ['nullable', 'string', 'exists:plans,slug'],
            'device_name'  => ['nullable', 'string', 'max:100'],
        ]);
 
        try {
            $result = DB::transaction(function () use ($request) {
 
                $tenant = Tenant::create([
                    'name'      => $request->company_name,
                    'subdomain' => strtolower($request->subdomain),
                    'email'     => $request->email,
                    'phone'     => $request->phone,
                    'status'    => 'active',
                ]);
 
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name'      => trim($request->first_name . ' ' . $request->last_name),
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'phone'     => $request->phone,
                    'user_type' => 'tenant_admin',
                    'is_active' => true,
                ]);
 
                $user->assignRole('tenant_admin');
 
                // Plan assign — custom (Enterprise) plans are sales-assisted.
                $plan = Plan::where('slug', $request->plan ?? 'free')->where('is_custom', false)->first()
                     ?? Plan::where('slug', 'free')->first();

                if ($plan) {
                    // trial_days > 0 → N-day trial; 0 + ₹0 plan → permanent free;
                    // 0 + paid plan → trial ending now (client must pay to activate).
                    $trialDays = (int) $plan->trial_days;
                    $isFree    = (float) $plan->monthly_price === 0.0;

                    if ($trialDays > 0) {
                        $ends   = now()->addDays($trialDays);
                        $window = ['status' => 'trial', 'trial_ends_at' => $ends, 'ends_at' => $ends];
                    } elseif ($isFree) {
                        $window = ['status' => 'active', 'trial_ends_at' => null, 'ends_at' => null];
                    } else {
                        $window = ['status' => 'trial', 'trial_ends_at' => now(), 'ends_at' => now()];
                    }

                    $tenant->subscriptions()->create(array_merge(
                        ['plan_id' => $plan->id, 'started_at' => now()],
                        $window,
                    ));
                }
 
                // Create token
                $token = $user->createToken(
                    $request->device_name ?? 'api_token',
                    ['*']
                )->plainTextToken;
 
                return compact('tenant', 'user', 'token', 'plan');
            });
 
            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to Mishora CRM 🎉',
                'data'    => [
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                    'user'       => $this->formatUser($result['user']),
                ],
            ], 201);
 
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
 
    // ── Login ─────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);
 
        // Rate limit: 5 attempts / minute
        $key = 'login:' . Str::lower($request->email) . ':' . $request->ip();
 
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Try again in {$seconds} seconds.",
            ], 429);
        }
 
        $user = User::where('email', $request->email)->first();
 
        // Wrong credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }
 
        // Inactive account
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Contact support.',
            ], 403);
        }
 
        // Tenant suspended check
        if ($user->tenant && !$user->tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your workspace has been suspended. Contact support.',
            ], 403);
        }
 
        RateLimiter::clear($key);
 
        // Revoke old token for same device, create new
        $deviceName = $request->device_name ?? 'api_token';
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, ['*'])->plainTextToken;
 
        // Update last login
        $user->update(['last_login_at' => now()]);
 
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => $this->formatUser($user->fresh(['tenant', 'roles', 'permissions'])),
            ],
        ]);
    }
 
    // ── Logout ────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        // Sirf current token revoke karo
        $request->user()->currentAccessToken()->delete();
 
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
 
    // ── Logout from all devices ───────────────────────────────────
    public function logoutAll(Request $request): JsonResponse
    {
        // Sab tokens revoke karo (all devices)
        $request->user()->tokens()->delete();
 
        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices.',
        ]);
    }
 
    // ── Get current user (me) ─────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['tenant', 'tenant.subscription.plan']);
 
        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user),
        ]);
    }
 
    // ── Update profile ────────────────────────────────────────────
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
 
        $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:15'],
        ]);
 
        $user->update($request->only('name', 'phone'));
 
        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data'    => $this->formatUser($user->fresh()),
        ]);
    }
 
    // ── Change password ───────────────────────────────────────────
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
 
        $user = $request->user();
 
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }
 
        $user->update([
            'password' => Hash::make($request->password),
        ]);
 
        // Revoke all other tokens for security
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();
 
        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Other devices have been logged out.',
        ]);
    }
 
    // ── Upload avatar ─────────────────────────────────────────────
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);
 
        $user = $request->user();
 
        // Delete old avatar
        if ($user->avatar && Storage::exists('avatars/' . $user->avatar)) {
            Storage::delete('avatars/' . $user->avatar);
        }
 
        // Store new avatar
        $filename = $user->id . '_' . time() . '.' . $request->file('avatar')->extension();
        $request->file('avatar')->storeAs('avatars', $filename, 'public');
 
        $user->update(['avatar' => $filename]);
 
        return response()->json([
            'success' => true,
            'message' => 'Avatar updated.',
            'data'    => [
                'avatar_url' => asset('storage/avatars/' . $filename),
            ],
        ]);
    }
 
    // ── Forgot password ───────────────────────────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
 
        $status = Password::sendResetLink($request->only('email'));
 
        // Always return success (security — don't reveal if email exists)
        return response()->json([
            'success' => true,
            'message' => 'If this email is registered, a reset link has been sent.',
        ]);
    }
 
    // ── Reset password ────────────────────────────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
 
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
 
                // Revoke all tokens on password reset
                $user->tokens()->delete();
 
                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );
 
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. Please login again.',
            ]);
        }
 
        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 422);
    }
 
    // ── Helper: Format user response ──────────────────────────────
    private function formatUser(User $user): array
    {
        $tenant = $user->relationLoaded('tenant') ? $user->tenant : null;
        $sub    = $tenant?->relationLoaded('subscription') ? $tenant->subscription : null;
 
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'avatar_url'    => $user->avatar
                                ? asset('storage/avatars/' . $user->avatar)
                                : null,
            'user_type'     => $user->user_type,
            'is_active'     => $user->is_active,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'roles'         => $user->getRoleNames(),
            'permissions'   => $user->getAllPermissions()->pluck('name'),
            'tenant'        => $tenant ? [
                'id'        => $tenant->id,
                'name'      => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'logo_url'  => $tenant->logo ? asset('storage/logos/' . $tenant->logo) : null,
                'timezone'  => $tenant->timezone,
                'currency'  => $tenant->currency,
                'status'    => $tenant->status,
            ] : null,
            'subscription'  => $sub ? [
                'plan'          => $sub->plan?->name,
                'plan_slug'     => $sub->plan?->slug,
                'status'        => $sub->status,
                'billing_cycle' => $sub->billing_cycle,
                'trial_ends_at' => $sub->trial_ends_at?->toDateString(),
                'ends_at'       => $sub->ends_at?->toDateString(),
                'is_trial'      => $sub->status === 'trial',
                'days_left'     => $sub->ends_at ? now()->diffInDays($sub->ends_at, false) : null,
            ] : null,
        ];
    }
}