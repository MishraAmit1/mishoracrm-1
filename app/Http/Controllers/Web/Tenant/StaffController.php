<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findStaff(int $id): Staff
    {
        return Staff::where('id', $id)
                    ->where('tenant_id', $this->tenantId())
                    ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Staff::with(['user', 'department'])
            ->where('tenant_id', $this->tenantId());

        if ($request->filled('search')) {
            $query->whereHas('user', fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        $staff           = $query->latest()->paginate(15)->withQueryString();
        $departments     = Department::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $types           = Staff::employmentTypes();
        $assignableRoles = auth()->user()->can('staff.edit')
            ? Roles::assignableFor($this->tenantId())
            : collect();

        return view('tenant.staffs.index', compact('staff', 'departments', 'types', 'assignableRoles'));
    }

    // ── Bulk role change ─────────────────────────────────────────
    public function bulkAssignRole(Request $request): RedirectResponse
    {
        $assignable = Roles::assignableFor($this->tenantId());

        $data = $request->validate([
            'staff_ids'   => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['integer'],
            'role'        => ['required', Rule::in($assignable->pluck('name'))],
        ]);

        $members = Staff::with('user')
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', $data['staff_ids'])
            ->get();

        $changed = 0;
        $skippedSelf = false;

        foreach ($members as $member) {
            if (! $member->user) {
                continue;
            }
            if ($member->user->id === auth()->id()) {
                $skippedSelf = true;
                continue;
            }
            $member->user->syncRoles([$data['role']]);
            $member->user->update(['user_type' => Roles::userTypeFor($data['role'])]);
            $changed++;
        }

        $msg = "Role changed to '" . Roles::label($data['role']) . "' for {$changed} staff member(s).";
        if ($skippedSelf) {
            $msg .= ' Your own role was left unchanged — edit it from your profile.';
        }

        return back()->with($changed ? 'success' : 'error', $changed ? $msg : 'No staff members were updated.');
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $departments     = Department::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $assignableRoles = Roles::assignableFor($this->tenantId());
        $types           = Staff::employmentTypes();

        return view('tenant.staffs.create', compact('departments', 'assignableRoles', 'types'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $assignableRoles = Roles::assignableFor($this->tenantId());

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'min:8', 'confirmed'],
            'phone'            => ['nullable', 'string', 'max:15'],
            'role'             => ['required', Rule::in($assignableRoles->pluck('name'))],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'designation'      => ['nullable', 'string', 'max:255'],
            'employee_code'    => ['nullable', 'string', 'max:50'],
            'salary'           => ['nullable', 'numeric', 'min:0'],
            'joining_date'     => ['nullable', 'date'],
            'employment_type'  => ['nullable', 'in:full_time,part_time,contract,intern'],
        ]);

        // Check subscription user limit
        $plan = auth()->user()->tenant?->subscription?->plan;
        if ($plan) {
            $maxUsers = (int) ($plan->features['users'] ?? 0);
            if ($maxUsers > 0) {
                $currentCount = User::withoutGlobalScopes()
                    ->where('tenant_id', $this->tenantId())
                    ->where('is_active', true)
                    ->count();
                if ($currentCount >= $maxUsers) {
                    return back()->withInput()->with(
                        'error',
                        "User limit reached. Your {$plan->name} plan allows {$maxUsers} users. Please upgrade your subscription to add more users."
                    );
                }
            }
        }

        // 1. Create user
        $user = User::create([
            'tenant_id'  => $this->tenantId(),
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'user_type'  => Roles::userTypeFor($request->role),
            'is_active'  => true,
        ]);

        // 2. Assign role
        $user->syncRoles([$request->role]);

        // 3. Create staff record
        Staff::create([
            'tenant_id'       => $this->tenantId(),
            'user_id'         => $user->id,
            'department_id'   => $request->department_id,
            'designation'     => $request->designation,
            'employee_code'   => $request->employee_code,
            'salary'          => $request->salary,
            'joining_date'    => $request->joining_date,
            'employment_type' => $request->employment_type ?? 'full_time',
        ]);

        return redirect()->route('tenant.staffs.index')
            ->with('success', "{$request->name} added as staff successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): View
    {
        $staff = $this->findStaff($id);
        $staff->load(['user.roles', 'department']);

        return view('tenant.staffs.show', compact('staff'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int $id): View
    {
        $staff           = $this->findStaff($id);
        $staff->load(['user.roles', 'department']);
        $departments     = Department::where('tenant_id', $this->tenantId())->orderBy('name')->get();
        $assignableRoles = Roles::assignableFor($this->tenantId());
        $types           = Staff::employmentTypes();

        return view('tenant.staffs.edit', compact('staff', 'departments', 'assignableRoles', 'types'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id): RedirectResponse
    {
        $staff = $this->findStaff($id);

        $assignableRoles = Roles::assignableFor($this->tenantId());

        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', "unique:users,email,{$staff->user_id}"],
            'phone'           => ['nullable', 'string', 'max:15'],
            'role'            => ['required', Rule::in($assignableRoles->pluck('name'))],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'designation'     => ['nullable', 'string', 'max:255'],
            'employee_code'   => ['nullable', 'string', 'max:50'],
            'salary'          => ['nullable', 'numeric', 'min:0'],
            'joining_date'    => ['nullable', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'password'        => ['nullable', 'min:8', 'confirmed'],
        ]);

        // Update user
        $userData = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        $staff->user->update($userData);

        // Update role (+ keep user_type in sync with the role)
        $staff->user->syncRoles([$request->role]);
        $staff->user->update(['user_type' => Roles::userTypeFor($request->role)]);

        // Update staff record
        $staff->update([
            'department_id'   => $request->department_id,
            'designation'     => $request->designation,
            'employee_code'   => $request->employee_code,
            'salary'          => $request->salary,
            'joining_date'    => $request->joining_date,
            'employment_type' => $request->employment_type ?? 'full_time',
        ]);

        return redirect()->route('tenant.staffs.show', $id)
            ->with('success', 'Staff updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): RedirectResponse
    {
        $staff = $this->findStaff($id);
        $name  = $staff->user->name;

        // Deactivate user instead of hard delete
        $staff->user->update(['is_active' => false]);
        $staff->delete();

        return redirect()->route('tenant.staffs.index')
            ->with('success', "{$name} removed from staff.");
    }

    // ── Activate / Deactivate ─────────────────────────────────────
    public function activate(int $id): RedirectResponse
    {
        $staff = $this->findStaff($id);
        $staff->user->update(['is_active' => true]);

        return back()->with('success', 'Staff activated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $staff = $this->findStaff($id);
        $staff->user->update(['is_active' => false]);

        return back()->with('success', 'Staff deactivated.');
    }
}