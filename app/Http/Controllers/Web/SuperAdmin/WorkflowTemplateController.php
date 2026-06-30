<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowTemplateController extends Controller
{
    public function index(): View
    {
        $templates = WorkflowTemplate::orderBy('sort_order')->orderByDesc('created_at')->get();
        return view('superadmin.workflow-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('superadmin.workflow-templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'category'     => ['required', 'string', 'max:100'],
            'suitable_for' => ['nullable', 'string', 'max:255'],
            'features'     => ['nullable', 'array'],
            'features.*'   => ['string', 'max:255'],
            'icon_type'    => ['required', 'string', 'max:50'],
            'color'        => ['required', 'string', 'max:30'],
            'is_active'    => ['boolean'],
            'sort_order'   => ['integer', 'min:0'],
        ]);

        $data['features']  = array_filter($data['features'] ?? []);
        $data['is_active'] = $request->boolean('is_active', true);

        WorkflowTemplate::create($data);

        return redirect()->route('superadmin.workflow-templates.index')
            ->with('success', 'Workflow template created.');
    }

    public function edit(WorkflowTemplate $workflowTemplate): View
    {
        return view('superadmin.workflow-templates.edit', ['template' => $workflowTemplate]);
    }

    public function update(Request $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'category'     => ['required', 'string', 'max:100'],
            'suitable_for' => ['nullable', 'string', 'max:255'],
            'features'     => ['nullable', 'array'],
            'features.*'   => ['string', 'max:255'],
            'icon_type'    => ['required', 'string', 'max:50'],
            'color'        => ['required', 'string', 'max:30'],
            'is_active'    => ['boolean'],
            'sort_order'   => ['integer', 'min:0'],
        ]);

        $data['features']  = array_filter($data['features'] ?? []);
        $data['is_active'] = $request->boolean('is_active', true);

        $workflowTemplate->update($data);

        return redirect()->route('superadmin.workflow-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $workflowTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function toggle(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $workflowTemplate->update(['is_active' => !$workflowTemplate->is_active]);
        return back()->with('success', 'Template status updated.');
    }
}
