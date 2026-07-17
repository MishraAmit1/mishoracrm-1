<?php

namespace App\Http\Controllers\Web\Tenant;


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

        $summary = Task::query()
            ->where('tenant_id', $tenantId)
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

    // create method
    public function create(Request $request)
    {
        $staffList = $this->getStaffList();
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::orderBy('name')->get(['id', 'name']);
        $deals     = Deal::orderBy('created_at')->get(['id', 'title']); 

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

    // store method
    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['tenant_id']  = auth()->user()->tenant_id;
        $data['created_by'] = auth()->id();

        if ($data['status'] === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now()->toDateString();
        }
        if(!empty($data['taskable_type'])) {
           if($data['taskable_type'] === 'lead'){
                $data['taskable_type'] = 'App\Models\Lead';
           }
           elseif($data['taskable_type'] === 'contact'){
                $data['taskable_type'] = 'App\Models\Contact';
           }
            elseif($data['taskable_type'] === 'deal'){
                $data['taskable_type'] = 'App\Models\Deal';   
         }
        } else {
            $data['taskable_type'] = null;
            $data['taskable_id'] = null;
        }


        Task::create($data);

        return redirect()->route('tenant.tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(int|string $id)
    {
        $task = $this->findTask($id);
        return view('tenant.tasks.show', compact('task'));
    }

    public function edit(int|string $id)
    {
        $task      = $this->findTask($id);
        $staffList = $this->getStaffList();
        return view('tenant.tasks.edit', compact('task', 'staffList'));
    }

    public function update(TaskRequest $request, int|string $id)
    {
        $task = $this->findTask($id);

        $data = $request->validated();

        if (!empty($data['taskable_type'])) {
            if ($data['taskable_type'] === 'lead') {
                $data['taskable_type'] = 'App\Models\Lead';
            } elseif ($data['taskable_type'] === 'contact') {
                $data['taskable_type'] = 'App\Models\Contact';
            } elseif ($data['taskable_type'] === 'deal') {
                $data['taskable_type'] = 'App\Models\Deal';
            }
        } else {
            // The edit form doesn't reliably resubmit the existing relation,
            // so an empty selection means "unchanged", not "clear it" —
            // taskable_type/taskable_id are NOT NULL columns.
            unset($data['taskable_type'], $data['taskable_id']);
        }

        if (($data['status'] ?? null) === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now()->toDateString();
        }

        $task->update($data);

        return redirect()->route('tenant.tasks.show', $task->id)->with('success', 'Task updated successfully.');
    }

    public function destroy(int|string $id)
    {
        $task = $this->findTask($id);
        $task->delete();

        return redirect()->route('tenant.tasks.index')->with('success', 'Task deleted successfully.');
    }

    // method to update task status via AJAX
    public function updateStatus(Request $request, int|string $id)
    {
        $task = $this->findTask($id);
        $task->status = $request->status;
        if ($task->status === 'completed' && empty($task->completed_at)) {
            $task->completed_at = now()->toDateString();
        }
        $task->save();

        return response()->json(['success' => true]);
    }
}
