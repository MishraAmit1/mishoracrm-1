<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    public function index(Request $request): View
    {
        $query = AuditLog::forTenant($this->tenantId())
            ->with('user')
            ->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('model_label', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $users = User::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $modelTypes = AuditLog::forTenant($this->tenantId())
            ->whereNotNull('model_type')
            ->distinct()
            ->pluck('model_type')
            ->map(fn($t) => ['value' => $t, 'label' => class_basename($t)])
            ->sortBy('label')
            ->values();

        $actions = ['created', 'updated', 'deleted', 'restored', 'login', 'logout'];

        return view('tenant.audit-logs.index', compact('logs', 'users', 'modelTypes', 'actions'));
    }

    public function show(int $id): View
    {
        $log = AuditLog::forTenant($this->tenantId())
            ->with('user')
            ->findOrFail($id);

        return view('tenant.audit-logs.show', compact('log'));
    }
}
