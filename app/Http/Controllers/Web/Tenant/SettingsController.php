<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    // ── My Profile — read-only overview ──────────────────────────
    public function profile(): View
    {
        $user = auth()->user()->load(['roles', 'tenant', 'staff.department']);
        return view('tenant.settings.profile', compact('user'));
    }

    // ── Index — show settings page ────────────────────────────────
    public function index(): View
    {
        $user   = auth()->user()->load('tenant');
        $tenant = $user->tenant;

        return view('tenant.settings.index', compact('user', 'tenant'));
    }

    // ── Update profile ────────────────────────────────────────────
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', "unique:users,email,{$user->id}"],
            'phone'       => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'designation' => $request->designation,
        ]);

        return back()
            ->with('success', 'Profile updated successfully.')
            ->with('active_tab', 'profile');
    }

    // ── Update password ───────────────────────────────────────────
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->with('active_tab', 'password');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()
            ->with('success', 'Password changed successfully.')
            ->with('active_tab', 'password');
    }

    // ── Upload avatar ─────────────────────────────────────────────
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store("avatars/{$user->tenant_id}", 'public');
        $user->update(['avatar' => $path]);

        return back()
            ->with('success', 'Avatar updated.')
            ->with('active_tab', 'profile');
    }

    // ── Update company settings ───────────────────────────────────
    public function updateCompany(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city'    => ['nullable', 'string', 'max:100'],
            'state'   => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'gst'     => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Extra settings in JSON column
        $settings = $tenant->settings ?? [];
        $settings = array_merge($settings, [
            'address' => $request->address,
            'city'    => $request->city,
            'state'   => $request->state,
            'pincode' => $request->pincode,
            'gst'     => $request->gst,
            'website' => $request->website,
        ]);
        $tenant->update(['settings' => $settings]);

        return back()
            ->with('success', 'Company details updated.')
            ->with('active_tab', 'company');
    }
}