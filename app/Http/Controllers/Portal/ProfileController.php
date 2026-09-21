<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Edits the platform-level Customer row only. It deliberately does NOT write
// through to any shop's Contact — that would be a tenant-data write from a
// customer-guard request.
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('portal.profile.edit', ['customer' => Auth::guard('customer')->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name'  => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        $email = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;

        // A changed email is unproven again — it must never be usable as a linking key.
        $emailChanged = $email !== ($customer->email ? mb_strtolower($customer->email) : null);

        $customer->forceFill([
            'name'  => $data['name'] ?? null,
            'email' => $email ?: null,
        ]);

        if ($emailChanged) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        return redirect()->route('portal.wallet.index')->with('success', 'Profile updated.');
    }
}
