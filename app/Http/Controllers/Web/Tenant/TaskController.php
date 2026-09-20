<?php

namespace App\Http\Controllers\Web\Tenant;


use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Contact;
use App\Models\CustomFieldValue;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskSavedFilter;
use App\Models\TaskTemplate;
use App\Models\TenantFieldAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    // ── Notify the assignee when a task is assigned to them ────────
    private function notifyAssignment(Task $task): void
    {
        if (!$task->assigned_to || $task->assigned_to === auth()->id()) {
            return;
        }

        NotificationService::notify(
            'task.assigned',
            $task->assignedTo,
            ['title' => $task->title],
            auth()->user(),
            route('tenant.tasks.show', $task->id),
            $task
        );
    }

    // ── Notify the task creator + watchers when it's marked completed by someone else ──
    private function notifyCompletion(Task $task): void
    {
        $recipients = collect([$task->creator])
            ->merge($task->watchers)
            ->filter()
            ->reject(fn($user) => $user->id === auth()->id())
            ->unique('id');

        foreach ($recipients as $recipient) {
            NotificationService::notify(
                'task.completed',
                $recipient,
                ['title' => $task->title],
                auth()->user(),
                route('tenant.tasks.show', $task->id),
                $task
            );
        }
    }

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

    private function getCustomFields(): \Illuminate\Support\Collection
    {
        return TenantFieldAssignment::getActiveFields(auth()->user()->tenant_id, 'task');
    }

    // ── saveCustomFields — field_key + assignment_id upsert (mirrors LeadController) ──
    private function saveCustomFields(Task $task, array $data): void
    {
        if (empty($data)) return;

        $tenantId    = auth()->user()->tenant_id;
        $assignments = TenantFieldAssignment::where('tenant_id', $tenantId)
            ->where('module', 'task')
            ->where('is_active', true)
            ->with(['globalTemplate', 'customField'])
            ->get()
            ->keyBy('id');

        foreach ($data as $assignmentId => $value) {
            $assignment = $assignments->get($assignmentId);
            if (!$assignment) continue;

            $fieldInfo = $assignment->field_info;
            if (empty($fieldInfo)) continue;

            $fieldType = $fieldInfo['field_type'] ?? 'text';
            $fieldKey  = $fieldInfo['field_key']  ?? null;
            if (!$fieldKey) continue;

            $value = match ($fieldType) {
                'multi_select' => json_encode(
                    is_array($value) ? array_values(array_filter($value)) : []
                ),
                'checkbox' => ($value && $value !== '0') ? '1' : '0',
                'number'   => is_numeric($value) ? $value : null,
                default    => is_string($value) ? trim($value) : (string) ($value ?? ''),
            };

            if (($value === '' || is_null($value)) && !($fieldInfo['is_required'] ?? false)) {
                CustomFieldValue::where('tenant_id', $tenantId)
                    ->where('model_type', Task::class)
                    ->where('model_id', $task->id)
                    ->where('field_key', $fieldKey)
                    ->delete();
                continue;
            }

            CustomFieldValue::updateOrCreate(
                [
                    'tenant_id'  => $tenantId,
                    'model_type' => Task::class,
                    'model_id'   => $task->id,
                    'field_key'  => $fieldKey,
                ],
                [
                    'value'         => $value ?? '',
                    'assignment_id' => (int) $assignmentId,
                ]
            );
        }
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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
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

        $savedFilters = TaskSavedFilter::where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $sortableColumns = ['title', 'status', 'priority', 'due_at'];
        $sortCol = in_array($request->get('sort'), $sortableColumns, true) ? $request->get('sort') : 'created_at';
        $sortDir = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        if ($currentView === 'list') {
            $tasks = $query->orderBy($sortCol, $sortDir)->paginate(20)->withQueryString();

            return view('tenant.tasks.index', [
                'kanbanTasks'  => collect(),
                'tasks'        => $tasks,
                'stageSummary' => $summary,
                'cfgStatuses'  => $statuses,
                'staffList'    => $staffList,
                'savedFilters' => $savedFilters,
                'view'         => 'list',
            ]);
        }

        // Kanban view — cap cards per column so a status with hundreds/thousands
        // of tasks doesn't get loaded and rendered into the DOM all at once
        // (huge scroll, slow drag & drop). $summary (unaffected by the cap)
        // still carries the real per-status counts for accurate headers and
        // a "view all in list" link when a column is truncated.
        $kanbanLimit = 30;
        $kanbanData  = collect();
        foreach (array_keys($statuses) as $status) {
            $kanbanData[$status] = (clone $query)
                ->where('status', $status)
                ->latest()
                ->limit($kanbanLimit)
                ->get();
        }

        return view('tenant.tasks.index', [
            'kanbanTasks'  => $kanbanData,
            'kanbanLimit'  => $kanbanLimit,
            'stageSummary' => $summary,
            'cfgStatuses'  => $statuses,
            'staffList'    => $staffList,
            'savedFilters' => $savedFilters,
            'view'         => 'kanban',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Saved filters
    // ═══════════════════════════════════════════════════════════════

    public function storeSavedFilter(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $filters = $request->only(['view', 'search', 'stage', 'priority', 'assigned_to', 'sort', 'dir']);
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        TaskSavedFilter::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id'   => auth()->id(),
            'name'      => $request->name,
            'filters'   => $filters,
        ]);

        return back()->with('success', 'Filter saved.');
    }

    public function destroySavedFilter(int|string $id): RedirectResponse
    {
        TaskSavedFilter::where('tenant_id', auth()->user()->tenant_id)
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();

        return back()->with('success', 'Saved filter removed.');
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

        $customFields = $this->getCustomFields();
        $templates    = TaskTemplate::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();

        return view('tenant.tasks.create', compact('staffList', 'contacts', 'leads', 'deals', 'contact', 'lead', 'deal', 'customFields', 'templates'));
    }

    // ── Comma-separated "tags" input -> clean array ─────────────────
    private function parseTags(array $data): array
    {
        if (!array_key_exists('tags', $data)) {
            return $data;
        }

        $data['tags'] = collect(explode(',', (string) $data['tags']))
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($data['tags'])) {
            $data['tags'] = null;
        }

        return $data;
    }

    // ── Blank recurrence inputs -> clean defaults ────────────────────
    private function parseRecurrence(array $data): array
    {
        if (empty($data['recurrence_type'])) {
            $data['recurrence_type'] = 'none';
        }

        if ($data['recurrence_type'] === 'none') {
            $data['recurrence_interval'] = 1;
            $data['recurrence_end_date'] = null;
        } elseif (empty($data['recurrence_end_date'])) {
            $data['recurrence_end_date'] = null;
        }

        return $data;
    }

    // ── Blank number inputs -> null (avoid casting "" to decimal) ────
    private function parseHours(array $data): array
    {
        foreach (['estimated_hours', 'actual_hours'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
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
        $data = $this->parseTags($data);
        $data = $this->parseRecurrence($data);
        $data = $this->parseHours($data);

        $task = Task::create($data);

        $this->saveCustomFields($task, $request->input('custom_fields', []));
        $this->applyTemplateChecklist($task, $request->input('template_id'));

        $this->notifyAssignment($task);

        return redirect()->route('tenant.tasks.index')->with('success', 'Task created successfully.');
    }

    // ── Copy a template's checklist items onto a freshly created task ──
    private function applyTemplateChecklist(Task $task, mixed $templateId): void
    {
        if (empty($templateId)) return;

        $template = TaskTemplate::where('id', $templateId)
            ->where('tenant_id', $task->tenant_id)
            ->first();

        if (!$template || empty($template->checklist_items)) return;

        foreach ($template->checklist_items as $position => $title) {
            TaskChecklistItem::create([
                'tenant_id'  => $task->tenant_id,
                'task_id'    => $task->id,
                'title'      => $title,
                'position'   => $position,
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function show(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('view', $task);

        $task->load([
            'checklistItems.creator',
            'comments.user',
            'attachments.uploadedBy',
            'watchers',
            'dependencies',
            'dependents',
        ]);

        $staffList    = $this->getStaffList();
        $customFields = $this->getCustomFields();
        $customValues = CustomFieldValue::getByKeyForModel($task);
        $auditLogs    = $task->auditLogs()->with('user')->limit(15)->get();

        $existingDepIds = $task->dependencies->pluck('id')->all();
        $otherTasks = Task::where('tenant_id', $task->tenant_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $existingDepIds)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('tenant.tasks.show', compact('task', 'staffList', 'customFields', 'customValues', 'auditLogs', 'otherTasks'));
    }

    public function edit(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        $staffList = $this->getStaffList();
        ['contacts' => $contacts, 'leads' => $leads, 'deals' => $deals] = $this->getRelatableRecords();
        $customFields = $this->getCustomFields();
        $customValues = CustomFieldValue::getByAssignmentForModel($task);
        return view('tenant.tasks.edit', compact('task', 'staffList', 'contacts', 'leads', 'deals', 'customFields', 'customValues'));
    }

    public function update(TaskRequest $request, int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $data = $request->validated();

        $data = $this->normalizeTaskable($data);
        $data = $this->parseTags($data);
        $data = $this->parseRecurrence($data);
        $data = $this->parseHours($data);

        $wasCompleted  = $task->status === 'completed';
        $previousOwner = $task->assigned_to;

        if (($data['status'] ?? null) === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now()->toDateString();
        }

        if (($data['status'] ?? null) === 'completed' && $task->hasIncompleteDependencies()) {
            return back()->withInput()->with('error', 'This task is blocked by incomplete dependencies and cannot be marked completed yet.');
        }

        $task->update($data);
        $this->saveCustomFields($task, $request->input('custom_fields', []));

        if ($task->assigned_to && $task->assigned_to !== $previousOwner) {
            $this->notifyAssignment($task);
        }

        if (!$wasCompleted && $task->status === 'completed') {
            $this->notifyCompletion($task);
            $task->createNextOccurrence();
        }

        return redirect()->route('tenant.tasks.show', $task->id)->with('success', 'Task updated successfully.');
    }

    public function destroy(int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        $task->delete();

        return redirect()->route('tenant.tasks.index')->with('success', 'Task deleted successfully.');
    }

    // method to update task status via AJAX (Kanban drag-and-drop)
    public function updateStatus(Request $request, int|string $id)
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(config('task_fields.stages')))],
        ]);

        $newStatus    = $validated['status'];
        $wasCompleted = $task->status === 'completed';

        if ($newStatus === 'completed' && $task->hasIncompleteDependencies()) {
            return response()->json([
                'success' => false,
                'message' => 'This task is blocked by incomplete dependencies and cannot be marked completed yet.',
            ], 422);
        }

        $task->status = $newStatus;
        if ($task->status === 'completed' && empty($task->completed_at)) {
            $task->completed_at = now()->toDateString();
        }
        $task->save();

        if (!$wasCompleted && $task->status === 'completed') {
            $this->notifyCompletion($task);
            $task->createNextOccurrence();
        }

        return response()->json(['success' => true, 'status' => $task->status]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Checklist items
    // ═══════════════════════════════════════════════════════════════

    public function storeChecklistItem(Request $request, int|string $id): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $request->validate(['title' => ['required', 'string', 'max:255']]);

        TaskChecklistItem::create([
            'tenant_id'  => auth()->user()->tenant_id,
            'task_id'    => $task->id,
            'title'      => $request->title,
            'position'   => $task->checklistItems()->count(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Checklist item added.');
    }

    public function toggleChecklistItem(int|string $id, TaskChecklistItem $item): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        abort_unless($item->task_id === $task->id, 404);

        $item->update(['is_done' => !$item->is_done]);

        return back()->with('success', 'Checklist updated.');
    }

    public function destroyChecklistItem(int|string $id, TaskChecklistItem $item): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        abort_unless($item->task_id === $task->id, 404);

        $item->delete();

        return back()->with('success', 'Checklist item removed.');
    }

    // ═══════════════════════════════════════════════════════════════
    // Comments
    // ═══════════════════════════════════════════════════════════════

    public function storeComment(Request $request, int|string $id): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('view', $task);

        $request->validate(['body' => ['required', 'string', 'max:5000']]);

        TaskComment::create([
            'tenant_id' => auth()->user()->tenant_id,
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'body'      => $request->body,
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroyComment(int|string $id, TaskComment $comment): RedirectResponse
    {
        $task = $this->findTask($id);
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($comment->user_id === auth()->id() || auth()->user()->user_type === 'superadmin', 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    // ═══════════════════════════════════════════════════════════════
    // Attachments
    // ═══════════════════════════════════════════════════════════════

    public function storeAttachment(Request $request, int|string $id): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $request->validate([
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        foreach ($request->file('attachments') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs("tasks/{$tenantId}/{$task->id}", $filename, 'public');

            TaskAttachment::create([
                'tenant_id'     => $tenantId,
                'task_id'       => $task->id,
                'uploaded_by'   => auth()->id(),
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Attachment(s) uploaded successfully.');
    }

    public function destroyAttachment(int|string $id, TaskAttachment $attachment): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);
        abort_unless($attachment->task_id === $task->id, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment deleted.');
    }

    // ═══════════════════════════════════════════════════════════════
    // Watchers
    // ═══════════════════════════════════════════════════════════════

    public function storeWatcher(Request $request, int|string $id): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('view', $task);

        $request->validate(['user_id' => ['required', 'exists:users,id']]);

        $watcher = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($request->user_id);
        $task->watchers()->syncWithoutDetaching([$watcher->id => ['tenant_id' => $task->tenant_id]]);

        return back()->with('success', 'Watcher added.');
    }

    public function destroyWatcher(int|string $id, int|string $userId): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('view', $task);

        $task->watchers()->detach($userId);

        return back()->with('success', 'Watcher removed.');
    }

    // ═══════════════════════════════════════════════════════════════
    // Dependencies ("blocked by")
    // ═══════════════════════════════════════════════════════════════

    public function storeDependency(Request $request, int|string $id): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $request->validate(['depends_on_task_id' => ['required', 'integer']]);

        $blocker = Task::where('tenant_id', $task->tenant_id)->find($request->depends_on_task_id);

        if (!$blocker) {
            return back()->with('error', 'Task not found.');
        }

        if ($task->wouldCreateCycle($blocker->id)) {
            return back()->with('error', 'Cannot add this dependency — it would create a circular reference.');
        }

        $task->dependencies()->syncWithoutDetaching([$blocker->id => ['tenant_id' => $task->tenant_id]]);

        return back()->with('success', 'Dependency added.');
    }

    public function destroyDependency(int|string $id, int|string $dependsOnId): RedirectResponse
    {
        $task = $this->findTask($id);
        $this->authorize('modify', $task);

        $task->dependencies()->detach($dependsOnId);

        return back()->with('success', 'Dependency removed.');
    }

    // ═══════════════════════════════════════════════════════════════
    // Bulk actions
    // ═══════════════════════════════════════════════════════════════

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:status,assign,delete'],
            'value'  => ['nullable'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $tasks = Task::where('tenant_id', $tenantId)
            ->whereIn('id', $request->ids)
            ->get()
            ->filter(fn($task) => auth()->user()->can('modify', $task));

        if ($tasks->isEmpty()) {
            return back()->with('error', 'No tasks could be updated.');
        }

        switch ($request->action) {
            case 'status':
                if (!in_array($request->value, array_keys(config('task_fields.stages')), true)) {
                    return back()->with('error', 'Invalid status.');
                }
                $blockedCount = 0;
                foreach ($tasks as $task) {
                    if ($request->value === 'completed' && $task->hasIncompleteDependencies()) {
                        $blockedCount++;
                        continue;
                    }

                    $wasCompleted = $task->status === 'completed';
                    $task->status = $request->value;
                    if ($task->status === 'completed' && empty($task->completed_at)) {
                        $task->completed_at = now()->toDateString();
                    }
                    $task->save();

                    if (!$wasCompleted && $task->status === 'completed') {
                        $this->notifyCompletion($task);
                        $task->createNextOccurrence();
                    }
                }
                if ($blockedCount > 0) {
                    return back()->with('error', "{$blockedCount} task(s) skipped — blocked by incomplete dependencies.");
                }
                break;

            case 'assign':
                $assignee = $request->filled('value')
                    ? User::where('tenant_id', $tenantId)->find($request->value)
                    : null;

                foreach ($tasks as $task) {
                    $task->update(['assigned_to' => $assignee?->id]);
                    if ($assignee) {
                        $this->notifyAssignment($task);
                    }
                }
                break;

            case 'delete':
                foreach ($tasks as $task) {
                    $task->delete();
                }
                break;
        }

        return back()->with('success', "{$tasks->count()} task(s) updated.");
    }
}
