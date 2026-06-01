<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function validateModule(string $module): void
    {
        abort_unless(array_key_exists($module, CustomField::modules()), 404);
    }

    // ── Overview — all modules ────────────────────────────────────
    public function index(): View
    {
        $modules = CustomField::modules();

        $stats = collect($modules)->mapWithKeys(fn($m, $key) => [
            $key => [
                'total'    => CustomField::where('tenant_id', $this->tenantId())->where('module', $key)->count(),
                'active'   => CustomField::where('tenant_id', $this->tenantId())->where('module', $key)->where('is_active', true)->count(),
                'required' => CustomField::where('tenant_id', $this->tenantId())->where('module', $key)->where('is_required', true)->count(),
            ]
        ]);

        return view('tenant.custom-fields.index', compact('modules', 'stats'));
    }

    // ── Module fields list ────────────────────────────────────────
    public function module(string $module): View
    {
        $this->validateModule($module);

        $modules    = CustomField::modules();
        $fields     = CustomField::where('tenant_id', $this->tenantId())
            ->where('module', $module)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $fieldTypes = CustomField::fieldTypes();

        return view('tenant.custom-fields.module', compact(
            'module', 'modules', 'fields', 'fieldTypes'
        ));
    }

    // ── Create form ───────────────────────────────────────────────
    public function create(string $module): View
    {
        $this->validateModule($module);

        $modules    = CustomField::modules();
        $fieldTypes = CustomField::fieldTypes();

        return view('tenant.custom-fields.create', compact(
            'module', 'modules', 'fieldTypes'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request, string $module): RedirectResponse
    {
        $this->validateModule($module);

        $request->validate([
            'label'          => ['required', 'string', 'max:100'],
            'field_type'     => ['required', 'in:' . implode(',', array_keys(CustomField::fieldTypes()))],
            'options'        => ['nullable', 'array'],
            'options.*'      => ['required', 'string', 'max:100'],
            'placeholder'    => ['nullable', 'string', 'max:255'],
            'default_value'  => ['nullable', 'string', 'max:255'],
            'is_required'    => ['nullable', 'boolean'],
            'show_in_list'   => ['nullable', 'boolean'],
            'show_in_filter' => ['nullable', 'boolean'],
        ]);

        $fieldKey = CustomField::generateKey($request->label);

        // Check duplicate
        $exists = CustomField::where('tenant_id', $this->tenantId())
            ->where('module', $module)
            ->where('field_key', $fieldKey)
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->with('error', "Field with similar name already exists in this module.");
        }

        $maxOrder = CustomField::where('tenant_id', $this->tenantId())
            ->where('module', $module)
            ->max('sort_order') ?? 0;

        // Clean options
        $options = null;
        if (in_array($request->field_type, ['dropdown', 'multi_select']) && $request->options) {
            $options = array_values(array_filter($request->options, fn($o) => trim($o) !== ''));
        }

        CustomField::create([
            'tenant_id'      => $this->tenantId(),
            'module'         => $module,
            'label'          => $request->label,
            'field_key'      => $fieldKey,
            'field_type'     => $request->field_type,
            'options'        => $options,
            'placeholder'    => $request->placeholder,
            'default_value'  => $request->default_value,
            'is_required'    => $request->boolean('is_required'),
            'show_in_list'   => $request->boolean('show_in_list'),
            'show_in_filter' => $request->boolean('show_in_filter'),
            'is_active'      => true,
            'sort_order'     => $maxOrder + 1,
        ]);

        return redirect()
            ->route('tenant.custom-fields.module', $module)
            ->with('success', "Field '{$request->label}' added successfully.");
    }

    // ── Edit form ─────────────────────────────────────────────────
    public function edit(string $module, int $id): View
    {
        $this->validateModule($module);

        $field      = CustomField::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->where('module', $module)
            ->firstOrFail();

        $modules    = CustomField::modules();
        $fieldTypes = CustomField::fieldTypes();

        return view('tenant.custom-fields.edit', compact(
            'module', 'modules', 'field', 'fieldTypes'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, string $module, int $id): RedirectResponse
    {
        $this->validateModule($module);

        $field = CustomField::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->where('module', $module)
            ->firstOrFail();

        $request->validate([
            'label'          => ['required', 'string', 'max:100'],
            'placeholder'    => ['nullable', 'string', 'max:255'],
            'default_value'  => ['nullable', 'string', 'max:255'],
            'options'        => ['nullable', 'array'],
            'options.*'      => ['required', 'string', 'max:100'],
            'is_required'    => ['nullable', 'boolean'],
            'show_in_list'   => ['nullable', 'boolean'],
            'show_in_filter' => ['nullable', 'boolean'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        // Clean options
        $options = $field->options;
        if (in_array($field->field_type, ['dropdown', 'multi_select']) && $request->options) {
            $options = array_values(array_filter($request->options, fn($o) => trim($o) !== ''));
        }

        $field->update([
            'label'          => $request->label,
            'placeholder'    => $request->placeholder,
            'default_value'  => $request->default_value,
            'options'        => $options,
            'is_required'    => $request->boolean('is_required'),
            'show_in_list'   => $request->boolean('show_in_list'),
            'show_in_filter' => $request->boolean('show_in_filter'),
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('tenant.custom-fields.module', $module)
            ->with('success', "Field '{$field->label}' updated successfully.");
    }

    // ── Toggle active ─────────────────────────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $field = CustomField::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $field->update(['is_active' => !$field->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $field->is_active,
            'message'   => $field->is_active ? 'Field enabled.' : 'Field disabled.',
        ]);
    }

    // ── Reorder ───────────────────────────────────────────────────
    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $index => $id) {
            CustomField::where('id', $id)
                ->where('tenant_id', $this->tenantId())
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): RedirectResponse
    {
        $field = CustomField::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $module = $field->module;
        $label  = $field->label;

        $field->values()->delete();
        $field->delete();

        return redirect()
            ->route('tenant.custom-fields.module', $module)
            ->with('success', "Field '{$label}' deleted.");
    }
}