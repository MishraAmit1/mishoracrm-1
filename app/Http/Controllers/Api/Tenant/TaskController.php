<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()?->tenant_id ?? app('tenant_id');
    }

    private function findTask(int $id): Task
    {
        return Task::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'status'         => ['nullable', 'in:pending,in_progress,completed,cancelled'],
            'priority'       => ['nullable', 'in:low,medium,high'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string', 'max:50'],
            'taskable_type'  => ['nullable', 'in:lead,contact,deal,App\Models\Lead,App\Models\Contact,App\Models\Deal'],
            'taskable_id'    => ['nullable', 'integer'],
            'assigned_to'    => ['nullable', 'exists:users,id'],
            'due_at'         => ['nullable', 'date'],
            'estimated_hours'=> ['nullable', 'numeric', 'min:0', 'max:9999'],
            'actual_hours'   => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];
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
        } else {
            $data['taskable_type'] = null;
            $data['taskable_id']   = null;
        }

        return $data;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Task::where('tenant_id', $this->tenantId())
            ->with(['assignedTo', 'creator'])
            ->withCount(['checklistItems', 'comments', 'attachments']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
            });
        }

        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);

        $sortBy  = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowed = ['title', 'status', 'priority', 'due_at', 'created_at'];
        if (in_array($sortBy, $allowed, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $tasks   = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => TaskResource::collection($tasks->items()),
            'meta'    => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'per_page'     => $tasks->perPage(),
                'total'        => $tasks->total(),
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $data['tenant_id']  = $this->tenantId();
        $data['created_by'] = auth()->id();
        $data['status']     = $data['status'] ?? 'pending';
        $data['priority']   = $data['priority'] ?? 'medium';
        $data = $this->normalizeTaskable($data);

        $task = Task::create($data);
        $task->load(['assignedTo', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data'    => new TaskResource($task),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $task = $this->findTask($id);

        $task->load(['assignedTo', 'creator', 'checklistItems'])
            ->loadCount(['checklistItems', 'comments', 'attachments']);

        return response()->json([
            'success' => true,
            'data'    => new TaskResource($task),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $task = $this->findTask($id);

        $data = $request->validate($this->rules());
        $data = $this->normalizeTaskable($data);

        $wasCompleted = $task->status === 'completed';
        if (($data['status'] ?? null) === 'completed' && $task->hasIncompleteDependencies()) {
            return response()->json([
                'success' => false,
                'message' => 'This task is blocked by incomplete dependencies.',
            ], 422);
        }

        if (($data['status'] ?? null) === 'completed' && empty($task->completed_at)) {
            $data['completed_at'] = now()->toDateString();
        }

        $task->update($data);

        if (!$wasCompleted && $task->status === 'completed') {
            $task->createNextOccurrence();
        }

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data'    => new TaskResource($task->fresh(['assignedTo', 'creator'])),
        ]);
    }

    // ── Update status ─────────────────────────────────────────────
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = $this->findTask($id);

        $request->validate(['status' => ['required', 'in:pending,in_progress,completed,cancelled']]);

        if ($request->status === 'completed' && $task->hasIncompleteDependencies()) {
            return response()->json([
                'success' => false,
                'message' => 'This task is blocked by incomplete dependencies.',
            ], 422);
        }

        $wasCompleted = $task->status === 'completed';
        $task->status = $request->status;
        if ($task->status === 'completed' && empty($task->completed_at)) {
            $task->completed_at = now()->toDateString();
        }
        $task->save();

        if (!$wasCompleted && $task->status === 'completed') {
            $task->createNextOccurrence();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'data'    => new TaskResource($task->fresh(['assignedTo', 'creator'])),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $task = $this->findTask($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }
}
