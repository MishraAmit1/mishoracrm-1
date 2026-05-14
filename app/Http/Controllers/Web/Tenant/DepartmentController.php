<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function find(int $id): Department
    {
        return Department::where('id', $id)
                         ->where('tenant_id', $this->tenantId())
                         ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Department::withCount('staff')
            ->where('tenant_id', $this->tenantId());

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('tenant.departments.index', compact('departments'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        return view('tenant.departments.create');
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Department::create([
            'tenant_id'   => $this->tenantId(),
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('tenant.departments.index')
            ->with('success', "Department '{$request->name}' created.");
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int $id): View
    {
        $department = $this->find($id);
        return view('tenant.departments.edit', compact('department'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $dept = $this->find($id);
        $dept->update($request->only('name', 'description'));

        return redirect()->route('tenant.departments.index')
            ->with('success', 'Department updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): RedirectResponse
    {
        $dept = $this->find($id);

        // Check staff assigned
        if ($dept->staff()->count() > 0) {
            return back()->with('error', 'Cannot delete — staff members are assigned to this department.');
        }

        $name = $dept->name;
        $dept->delete();

        return redirect()->route('tenant.departments.index')
            ->with('success', "Department '{$name}' deleted.");
    }
}