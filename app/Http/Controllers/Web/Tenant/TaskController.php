<?php

namespace App\Http\Controllers\Web\Tenant;


use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function findTask(int|string $id)
    {
        return Task::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }


    private function getStaffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // index method — supports kanban and list views
    public function index(Request $request)
    {
        $tenantId    = auth()->user()->tenant_id;
        $currentView = $request->get('view', 'kanban');

        $query = Task::query()
            ->where('tenant_id', $tenantId)
            ->with(['assignedTo', 'creator']);

        $query = ViewScope::apply($query, 'tasks', auth()->user());

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // 'stage' param from URL maps to the status DB column
        if ($request->filled('stage')) {
            $query->where('status', $request->stage);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $statuses = config('task_fields.stages');

        $summary = ViewScope::apply(Task::query()->where('tenant_id', $tenantId), 'tasks', auth()->user())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $staffList = $this->getStaffList();

        if ($currentView === 'list') {
            $tasks = $query->latest()->paginate(20)->withQueryString();

            return view('tenant.tasks.index', [
                'kanbanTasks' => collect(),
                'tasks'       => $tasks,
                'stageSummary'=> $summary,
                'cfgStatuses' => $statuses,
                'staffList'   => $staffList,
                'view'        => 'list',
            ]);
        }

        // Kanban view — load all for drag-and-drop
        $allTasks   = $query->latest()->get();
        $kanbanData = collect();
        foreach (array_keys($statuses) as $status) {
            $kanbanData[$status] = $allTasks->where('status', $status)->values();
        }

        return view('tenant.tasks.index', [
            'kanbanTasks' => $kanbanData,
            'stageSummary'=> $summary,
            'cfgStatuses' => $statuses,
            'staffList'   => $staffList,
            'view'        => 'kanban',
        ]);
    }

    private function getRelatableRecords(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'contacts' => Contact::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'company']),
            'leads'    => Lead::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'deals'    => Deal::where('tenant_id', $tenantId)->orderBy('created_at')->get(['id', 'title']),
        ];
    }

    // create method
    public function create(Request $request)
    {
        $staffList = $this->getStaffList();
        ['contacts' => $contacts, 'leads' => $leads, 'deals' => $deals] = $this->getRelatableRecords();

        // Pre-fill contact/lead if coming from their pages
        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $deal = $request->filled('deal_id')
            ? Deal::where('id', $request->deal_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        

        return view('tenant.tasks.create', compact('staffList', 'contacts', 'leads', 'deals', 'contact', 'lead', 'deal'));
    }

    private function normalizeTaskable(array $data): array
    {
        $map = [
            'lead'    => 'App\Models\Lead',
            'contact' => 'App\Models\Contact',
            'deal'    => 'App\Models\Deal',
        ];

        if (!empty($data['taskable_type']) && !empty($data['taskable_id'])) {
            $data['taskable_type'] = $map[$data['taskable_type']] ?? $data['taskable_type'];
            $data['taskable_id']   = (int) $data['taskable_id'];
        } else {
            $data['taskable_type'] = null;
            $data['taskable_id']   = null;
        }

        return $data;
    }

    // store method
    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['tenant_id']  = auth()->user()->tenant_id;
        $data['created_by'] = auth()->id();

        if ($data['status'] === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now()->toDateString();
        }

        $data = $this->normalizeTaskable($data);

        Task::create($data);

        return redirect()->route('tenant.tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('view', $task);
        return view('tenant.tasks.show', compact('task'));
    }

    public function edit(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        $staffList = $this->getStaffList();
        ['contacts' => $contacts, 'leads' => $leads, 'deals' => $deals] = $this->getRelatableRecords();
        return view('tenant.tasks.edit', compact('task', 'staffList', 'contacts', 'leads', 'deals'));
    }

    public function update(TaskRequest $request, int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $data = $request->validated();

        $data = $this->normalizeTaskable($data);

        if (($data['status'] ?? null) === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now()->toDateString();
        }

        $task->update($data);

        return redirect()->route('tenant.tasks.show', $task->id)->with('success', 'Task updated successfully.');
    }

    public function destroy(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        $task->delete();

        return redirect()->route('tenant.tasks.index')->with('success', 'Task deleted successfully.');
    }

    // method to update task status via AJAX
    public function updateStatus(Request $request, int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        $task->status = $request->status;
        if ($task->status === 'completed' && empty($task->completed_at)) {
            $task->completed_at = now()->toDateString();
        }
        $task->save();

        return response()->json(['success' => true]);
    }
}
