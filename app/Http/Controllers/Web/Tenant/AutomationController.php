<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRequest;
use App\Models\WorkflowTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function index(): View
    {
        $templates   = WorkflowTemplate::active()->get();
        $myRequests  = WorkflowRequest::where('tenant_id', auth()->user()->tenant_id)
            ->with('template')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('tenant.automation.index', compact('templates', 'myRequests'));
    }

    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workflow_template_id' => ['nullable', 'exists:workflow_templates,id'],
            'business_type'        => ['required', 'string', 'max:255'],
            'problem_description'  => ['required', 'string', 'max:2000'],
            'contact_preference'   => ['required', 'in:whatsapp,email'],
            'contact_value'        => ['required', 'string', 'max:255'],
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id']   = auth()->id();

        WorkflowRequest::create($data);

        return response()->json(['success' => true, 'message' => 'Request submitted! We will contact you soon.']);
    }
}
