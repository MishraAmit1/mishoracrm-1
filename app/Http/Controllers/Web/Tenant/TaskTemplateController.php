<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    private function findTemplate(int|string $id): TaskTemplate
    {
        return TaskTemplate::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Turn a newline-separated textarea into a clean checklist array ──
    private function parseChecklistLines(?string $raw): ?array
    {
        if (!$raw) return null;

        $items = collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $items ?: null;
    }

    private function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'default_priority' => ['required', 'in:low,medium,high'],
            'default_tags'     => ['nullable', 'string', 'max:500'],
            'checklist_items'  => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function index()
    {
        $templates = TaskTemplate::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->paginate(20);

        return view('tenant.task-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('tenant.task-templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        TaskTemplate::create([
            'tenant_id'        => auth()->user()->tenant_id,
            'created_by'       => auth()->id(),
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'default_priority' => $data['default_priority'],
            'default_tags'     => $this->parseChecklistLines(str_replace(',', "\n", $data['default_tags'] ?? '')),
            'checklist_items'  => $this->parseChecklistLines($data['checklist_items'] ?? null),
        ]);

        return redirect()->route('tenant.task-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(int|string $id)
    {
        $template = $this->findTemplate($id);
        return view('tenant.task-templates.edit', compact('template'));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $template = $this->findTemplate($id);
        $data = $request->validate($this->rules());

        $template->update([
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'default_priority' => $data['default_priority'],
            'default_tags'     => $this->parseChecklistLines(str_replace(',', "\n", $data['default_tags'] ?? '')),
            'checklist_items'  => $this->parseChecklistLines($data['checklist_items'] ?? null),
        ]);

        return redirect()->route('tenant.task-templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $this->findTemplate($id)->delete();

        return redirect()->route('tenant.task-templates.index')->with('success', 'Template deleted.');
    }
}
