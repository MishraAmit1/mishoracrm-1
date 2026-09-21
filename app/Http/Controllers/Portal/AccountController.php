<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CustomerOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// "Delete my account" (docs/customer-portal-loyalty.txt §10 #11 / §12 privacy).
// Removes the platform-level identity only. Each shop's Contact — its points,
// stamps and history — is the shop's own business record and stays put; it is
// simply un-linked from this customer.
class AccountController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'confirm.in' => 'Type DELETE (in capitals) to confirm.',
        ]);

        $customer = Auth::guard('customer')->user();

        DB::transaction(function () use ($customer) {
            // Mass update: no model events, no tenant audit rows, tenant data untouched.
            Contact::withoutGlobalScopes()
                ->where('customer_id', $customer->id)
                ->update(['customer_id' => null, 'phone_verified' => false]);

            CustomerOtp::where('phone', $customer->phone)->delete();

            // Wipe the personal details before the soft delete. blocked_at is kept
            // on purpose so deleting can't be used to shake off a block.
            $customer->forceFill([
                'name'              => null,
                'email'             => null,
                'email_verified_at' => null,
                'pin_hash'          => null,
                'pin_attempts'      => 0,
                'pin_locked_until'  => null,
                'remember_token'    => Str::random(60),
            ])->save();

            $customer->delete();
        });

        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with(
            'success',
            'Your wallet account has been deleted. Shops keep the records they already had, but they are no longer linked to you.'
        );
    }
}
