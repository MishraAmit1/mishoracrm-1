<?php

namespace App\Http\Controllers\Web\Auth;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
 
// ═══════════════════════════════════════════════════════════════════
// ForgotPasswordController — Email bhejo reset link ke liye
// ═══════════════════════════════════════════════════════════════════
class ForgotPasswordController extends Controller
{
    // Show forgot password form
    public function show(): View
    {
        return view('auth.forgot-password');
    }
 
    // Send reset link
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
 
        // Laravel ka built-in password broker use karo
        $status = Password::sendResetLink(
            $request->only('email')
        );
 
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }
 
        // Email not found ya rate limited
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
 
 
// ═══════════════════════════════════════════════════════════════════
// ResetPasswordController — Naya password set karo
// ═══════════════════════════════════════════════════════════════════
class ResetPasswordController extends Controller
{
    // Show reset form (token + email URL se aata hai)
    public function show(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }
 
    // Handle password reset
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ]);
 
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                ])->save();
 
                // Revoke all tokens (API security)
                $user->tokens()->delete();
 
                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );
 
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Password reset successfully! Please login with your new password.');
        }
 
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}