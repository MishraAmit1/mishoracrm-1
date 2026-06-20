<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // ── Show login form ───────────────────────────────────────────
    public function show(): View
    {
        return view('auth.login');
    }

    // ── Handle login ──────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rate limit: 5 attempts per minute per IP+email
        $this->checkRateLimit($request);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Increment failed attempts
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Clear rate limiter on success
        RateLimiter::clear($this->throttleKey($request));

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        AuditLog::record([
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'action'      => 'login',
            'description' => "User \"{$user->name}\" logged in",
        ]);

        // Regenerate session
        $request->session()->regenerate();

        // Redirect based on role
        return $this->redirectAfterLogin($user);
    }

    // ── Logout ────────────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::record([
                'tenant_id'   => $user->tenant_id,
                'user_id'     => $user->id,
                'action'      => 'logout',
                'description' => "User \"{$user->name}\" logged out",
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    // ── Redirect based on user type ───────────────────────────────
    private function redirectAfterLogin($user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // return redirect()->route('tenant.dashboard');
        return redirect()->route('tenant.dashboard', [
            'tenant' => $user->tenant->subdomain
        ]);
    }

    // ── Rate limit check ──────────────────────────────────────────
    private function checkRateLimit(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')) . '|' . $request->ip()
        );
    }
}