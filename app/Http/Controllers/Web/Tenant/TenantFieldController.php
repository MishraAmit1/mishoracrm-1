<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Models\GlobalFieldTemplate;
use App\Models\TenantFieldAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantFieldController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Overview ──────────────────────────────────────────────────
    public function index(): View
    {
        $modules = CustomField::modules();

        $stats = collect($modules)->mapWithKeys(fn($m, $key) => [
            $key => [
                'active'     => TenantFieldAssignment::where('tenant_id', $this->tenantId())
                    ->where('module', $key)->where('is_active', true)->count(),
                'total'      => TenantFieldAssignment::where('tenant_id', $this->tenantId())
                    ->where('module', $key)->count(),
                'available'  => GlobalFieldTemplate::where('module', $key)->count(),
            ]
        ]);

        return view('tenant.field-manager.index', compact('modules', 'stats'));
    }

    // ── Module fields management ──────────────────────────────────
    public function module(string $module): View
    {
        abort_unless(array_key_exists($module, CustomField::modules()), 404);

        $tenantId = $this->tenantId();

        // Already assigned fields
        $assignments = TenantFieldAssignment::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->with(['globalTemplate', 'customField'])
            ->orderBy('sort_order')
            ->get();

        // Global templates not yet assigned
        $availableGlobal = GlobalFieldTemplate::unassignedForTenant($tenantId, $module);

        // Tenant's own custom fields not yet assigned
        $availableCustom = CustomField::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('is_active', true)
            ->whereNotIn('id', TenantFieldAssignment::where('tenant_id', $tenantId)
                ->where('module', $module)
                ->whereNotNull('custom_field_id')
                ->pluck('custom_field_id'))
            ->get();

        $modules    = CustomField::modules();
        $fieldTypes = CustomField::fieldTypes();

        return view('tenant.field-manager.module', compact(
            'module',
            'modules',
            'assignments',
            'availableGlobal',
            'availableCustom',
            'fieldTypes'
        ));
    }

    // ── Add global template field ─────────────────────────────────
    public function addGlobal(Request $request, string $module): RedirectResponse
    {
        $request->validate([
            'template_id' => ['required', 'exists:global_field_templates,id'],
        ]);

        TenantFieldAssignment::assignGlobal(
            $this->tenantId(),
            $module,
            $request->template_id,
            [
                'is_required'    => $request->boolean('is_required'),
                'show_in_list'   => $request->boolean('show_in_list'),
                'show_in_filter' => $request->boolean('show_in_filter'),
            ]
        );

        $template = GlobalFieldTemplate::find($request->template_id);

        return back()->with('success', "Field '{$template->label}' added to module.");
    }

    // ── Add custom field to module ────────────────────────────────
    public function addCustom(Request $request, string $module): RedirectResponse
    {
        $request->validate([
            'custom_field_id' => ['required', 'exists:custom_fields,id'],
        ]);

        // Verify it belongs to this tenant
        $field = CustomField::where('id', $request->custom_field_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        TenantFieldAssignment::assignCustom($this->tenantId(), $module, $field->id);

        return back()->with('success', "Custom field '{$field->label}' added to module.");
    }

    // ── Update assignment settings ────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = TenantFieldAssignment::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $assignment->update([
            'is_active'      => $request->boolean('is_active',   $assignment->is_active),
            'is_required'    => $request->boolean('is_required',  $assignment->is_required),
            'show_in_list'   => $request->boolean('show_in_list', $assignment->show_in_list),
            'show_in_filter' => $request->boolean('show_in_filter', $assignment->show_in_filter),
        ]);

        return response()->json(['success' => true]);
    }

    // ── Toggle active ─────────────────────────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $assignment = TenantFieldAssignment::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        // System fields cannot be disabled
        if ($assignment->globalTemplate?->is_system) {
            return response()->json(['success' => false, 'message' => 'System fields cannot be disabled.'], 403);
        }

        $assignment->update(['is_active' => !$assignment->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $assignment->is_active,
        ]);
    }

    // ── Reorder ───────────────────────────────────────────────────
    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $index => $id) {
            TenantFieldAssignment::where('id', $id)
                ->where('tenant_id', $this->tenantId())
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // ── Remove field from module ──────────────────────────────────
    public function remove(int $id): RedirectResponse
    {
        $assignment = TenantFieldAssignment::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        // Cannot remove system fields
        if ($assignment->globalTemplate?->is_system) {
            return back()->with('error', 'System fields cannot be removed.');
        }

        $label = $assignment->field_info['label'] ?? 'Field';
        $assignment->delete();

        return back()->with('success', "'{$label}' removed from module.");
    }
}
