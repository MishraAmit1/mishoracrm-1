<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ErrorLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ErrorLog::with(['user', 'tenant'])->latest('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            $query->where('http_status', $request->status);
        }

        if ($request->filled('resolved')) {
            $query->where('is_resolved', $request->resolved === '1');
        }

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('message', 'like', "%{$request->search}%")
                ->orWhere('url', 'like', "%{$request->search}%")
                ->orWhere('exception_class', 'like', "%{$request->search}%")
            );
        }

        $errors  = $query->paginate(20)->withQueryString();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'      => ErrorLog::count(),
            'unresolved' => ErrorLog::unresolved()->count(),
            'critical'   => ErrorLog::where('http_status', '>=', 500)->unresolved()->count(),
            'today'      => ErrorLog::whereDate('created_at', today())->count(),
        ];

        return view('superadmin.error-logs.index', compact('errors', 'tenants', 'stats'));
    }

    public function show(ErrorLog $errorLog): View
    {
        $errorLog->load(['user', 'tenant']);
        return view('superadmin.error-logs.show', compact('errorLog'));
    }

    public function resolve(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->update(['is_resolved' => true]);
        return back()->with('success', 'Error marked as resolved.');
    }

    public function resolveAll(Request $request): RedirectResponse
    {
        $query = ErrorLog::unresolved();

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $query->update(['is_resolved' => true]);

        return back()->with('success', 'All errors marked as resolved.');
    }

    public function destroy(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->delete();
        return back()->with('success', 'Error log deleted.');
    }
}
