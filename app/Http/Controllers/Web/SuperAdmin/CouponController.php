<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    private function tenantAdmins()
    {
        return User::withoutGlobalScope('tenant')
            ->where('user_type', 'tenant_admin')
            ->with('tenant')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'tenant_id']);
    }

    public function index(): View
    {
        $coupons = Coupon::with('users.tenant')->latest()->paginate(20);
        return view('superadmin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $users = $this->tenantAdmins();
        return view('superadmin.coupons.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'           => 'required|string|max:50|unique:coupons,code|regex:/^[A-Z0-9_\-]+$/',
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:255',
            'type'           => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'max_discount'   => 'nullable|numeric|min:0',
            'applicable_to'  => 'required|in:all,specific_user',
            'user_ids'       => 'required_if:applicable_to,specific_user|array|min:1',
            'user_ids.*'     => 'exists:users,id',
            'max_uses'       => 'nullable|integer|min:1',
            'expires_at'     => 'nullable|date|after:today',
            'is_active'      => 'boolean',
        ]);

        if ($data['type'] === 'percentage' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100.'])->withInput();
        }

        $data['user_id']   = null;
        $data['tenant_id'] = null;
        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        $userIds = $request->input('user_ids', []);
        unset($data['user_ids']);

        $coupon = Coupon::create($data);

        if ($data['applicable_to'] === 'specific_user' && !empty($userIds)) {
            $coupon->users()->sync($userIds);
        }

        return redirect()->route('superadmin.coupons.index')
            ->with('success', 'Coupon "' . $data['code'] . '" created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $users = $this->tenantAdmins();
        return view('superadmin.coupons.edit', compact('coupon', 'users'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validate([
            'code'           => 'required|string|max:50|unique:coupons,code,' . $coupon->id . '|regex:/^[A-Z0-9_\-]+$/',
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:255',
            'type'           => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'max_discount'   => 'nullable|numeric|min:0',
            'applicable_to'  => 'required|in:all,specific_user',
            'user_ids'       => 'required_if:applicable_to,specific_user|array|min:1',
            'user_ids.*'     => 'exists:users,id',
            'max_uses'       => 'nullable|integer|min:1',
            'expires_at'     => 'nullable|date',
            'is_active'      => 'boolean',
        ]);

        if ($data['type'] === 'percentage' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100.'])->withInput();
        }

        $data['user_id']   = null;
        $data['tenant_id'] = null;
        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        $userIds = $request->input('user_ids', []);
        unset($data['user_ids']);

        $coupon->update($data);

        if ($data['applicable_to'] === 'specific_user') {
            $coupon->users()->sync($userIds);
        } else {
            $coupon->users()->detach();
        }

        return redirect()->route('superadmin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }

    public function toggle(Coupon $coupon): RedirectResponse
    {
        $newState = !$coupon->is_active;
        $coupon->update(['is_active' => $newState]);
        return back()->with('success', 'Coupon ' . ($newState ? 'activated' : 'deactivated') . '.');
    }
}
