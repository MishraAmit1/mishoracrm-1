<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    // ── Sirf current tenant ke users ─────────────────────────────
    private function getStaffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
                   ->where('is_active', true)
                   ->orderBy('name')
                   ->get(['id', 'name']);
    }

    // ── Find lead — tenant scope ke saath ────────────────────────
    // Route::domain() use hone ki wajah se model injection
    // kaam nahi karta — manually find karo
    private function findLead(int|string $id): Lead
    {
        return Lead::where('id', $id)
                   ->where('tenant_id', auth()->user()->tenant_id)
                   ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Lead::with(['assignedTo'])->withCount(['tasks', 'followups'])->where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search'))      $query->search($request->search);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('source'))      $query->where('source', $request->source);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);
        if ($request->filled('date_from'))   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('created_at', '<=', $request->date_to);

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');
        $allowed = ['name', 'created_at', 'status', 'priority', 'lead_value', 'source'];
        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $leads = $query->paginate(20)->withQueryString();

        $counts = [
            'all'       => Lead::where('tenant_id', auth()->user()->tenant_id)->count(),
            'new'       => Lead::where('status', 'new')->where('tenant_id', auth()->user()->tenant_id)->count(),
            'contacted' => Lead::where('status', 'contacted')->where('tenant_id', auth()->user()->tenant_id)->count(),
            'qualified' => Lead::where('status', 'qualified')->where('tenant_id', auth()->user()->tenant_id)->count(),
            'converted' => Lead::where('status', 'converted')->where('tenant_id', auth()->user()->tenant_id)->count(),
            'lost'      => Lead::where('status', 'lost')->where('tenant_id', auth()->user()->tenant_id)->count(),
        ];

        $staffList  = $this->getStaffList();
        $sources    = Lead::sources();
        $statuses   = Lead::statuses();
        $priorities = Lead::priorities();

        return view('tenant.leads.index', compact(
            'leads', 'counts', 'staffList', 'sources', 'statuses', 'priorities'
        ));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $staffList  = $this->getStaffList();
        $sources    = Lead::sources();
        $statuses   = Lead::statuses();
        $priorities = Lead::priorities();

        return view('tenant.leads.create', compact(
            'staffList', 'sources', 'statuses', 'priorities'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(LeadRequest $request): RedirectResponse
    {
        $data               = $request->validated();
        $data['tenant_id']  = auth()->user()->tenant_id;
        $data['created_by'] = auth()->id();

        if (!empty($data['assigned_to'])) {
            $valid = User::where('id', $data['assigned_to'])
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->exists();
            if (!$valid) $data['assigned_to'] = null;
        }

        $lead = Lead::create($data);

        return redirect()
            ->route('tenant.leads.show', [
                'tenant' => auth()->user()->tenant->subdomain,
                'id'     => $lead->id,
            ])
            ->with('success', "Lead '{$lead->name}' created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $lead = $this->findLead($id);
        $lead->load([
            'assignedTo',
            'createdBy',
            'followups.assignedTo',
            'tasks.assignedTo',
            'reminders',
        ]);

        $staffList  = $this->getStaffList();
        $sources    = Lead::sources();
        $statuses   = Lead::statuses();
        $priorities = Lead::priorities();

        return view('tenant.leads.show', compact(
            'lead', 'staffList', 'sources', 'statuses', 'priorities'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $lead       = $this->findLead($id);
        $staffList  = $this->getStaffList();
        $sources    = Lead::sources();
        $statuses   = Lead::statuses();
        $priorities = Lead::priorities();

        return view('tenant.leads.edit', compact(
            'lead', 'staffList', 'sources', 'statuses', 'priorities'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(LeadRequest $request, int|string $id): RedirectResponse
    {
        $lead = $this->findLead($id);
        $data = $request->validated();

        if (!empty($data['assigned_to'])) {
            $valid = User::where('id', $data['assigned_to'])
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->exists();
            if (!$valid) $data['assigned_to'] = null;
        }

        if ($request->status === 'contacted' && $lead->status !== 'contacted') {
            $data['contacted_at'] = now();
        }
        if ($request->status === 'converted' && $lead->status !== 'converted') {
            $data['converted_at'] = now();
        }

        $lead->update($data);

        return redirect()
            ->route('tenant.leads.show', [
                'tenant' => auth()->user()->tenant->subdomain,
                'id'     => $lead->id,
            ])
            ->with('success', 'Lead updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $lead = $this->findLead($id);
        $name = $lead->name;
        $lead->delete();

        return redirect()
            ->route('tenant.leads.index', ['tenant' => auth()->user()->tenant->subdomain])
            ->with('success', "Lead '{$name}' deleted.");
    }

    // ── Assign ────────────────────────────────────────────────────
    public function assign(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $lead  = $this->findLead($id);
        $valid = User::where('id', $request->assigned_to)
                     ->where('tenant_id', auth()->user()->tenant_id)
                     ->exists();

        if (!$valid) {
            return back()->with('error', 'Invalid staff member.');
        }

        $lead->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Lead assigned.');
    }

    // ── Convert to Contact ────────────────────────────────────────
    public function convert(int|string $id): RedirectResponse
    {
        $lead = $this->findLead($id);

        if ($lead->isConverted()) {
            return back()->with('error', 'Lead is already converted.');
        }

        $contact = Contact::create([
            'tenant_id'   => $lead->tenant_id,
            'lead_id'     => $lead->id,
            'name'        => $lead->name,
            'phone'       => $lead->phone,
            'email'       => $lead->email,
            'company'     => $lead->company,
            'designation' => $lead->designation,
            'city'        => $lead->city,
            'state'       => $lead->state,
        ]);

        $lead->update([
            'status'       => 'converted',
            'converted_at' => now(),
        ]);

        return redirect()
            ->route('tenant.leads.index', ['tenant' => auth()->user()->tenant->subdomain])
            ->with('success', 'Lead converted to contact.');
    }

    // ── Update Status ─────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status'      => ['required', 'in:new,contacted,qualified,proposal,negotiation,converted,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $lead = $this->findLead($id);
        $data = ['status' => $request->status];

        if ($request->status === 'contacted') $data['contacted_at'] = now();
        if ($request->status === 'converted') $data['converted_at'] = now();
        if ($request->status === 'lost' && $request->filled('lost_reason')) {
            $data['lost_reason'] = $request->lost_reason;
        }

        $lead->update($data);

        return back()->with('success', 'Status updated.');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'         => ['required', 'array'],
            'ids.*'       => ['exists:leads,id'],
            'status'      => ['required', 'in:new,contacted,qualified,proposal,negotiation,converted,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'contacted') $data['contacted_at'] = now();
        if ($request->status === 'converted') $data['converted_at'] = now();
        if ($request->status === 'lost' && $request->filled('lost_reason')) {
            $data['lost_reason'] = $request->lost_reason;
        }

        Lead::whereIn('id', $request->ids)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->update($data);

        return back()->with('success', 'Statuses updated.');
    }

        // ── Convert to Contact ────────────────────────────────────────
    // public function convert(int|string $id): RedirectResponse
    // {
    //     $lead = $this->findLead($id); 
    //     // write logic code to convert lead to contact — create contact record, update lead status, etc.

    //     if ($lead->isConverted()) {
    //         return back()->with('error', 'Lead is already converted.');     
    //     }

    //     $contact = Contact::create([
    //         'tenant_id'   => $lead->tenant_id,
    //         'lead_id'     => $lead->id,
    //         'name'        => $lead->name,
    //         'phone'       => $lead->phone,
    //         'email'       => $lead->email,
    //         'company'     => $lead->company,
    //         'designation' => $lead->designation,
    //         'city'        => $lead->city,
    //         'state'       => $lead->state,
    //     ]);

    //     $lead->update([
    //         'status'       => 'converted',
    //         'converted_at' => now(),
    //     ]); 

    //     return redirect()
    //         ->route('tenant.leads.index', ['tenant' => auth()->user()->tenant])
    //         ->with('success', 'Lead converted to contact.'); 

    // }

    // lead data for contact form ke liye — jisse lead se contact create karte waqt lead ki details auto-fill ho jaye
    public function leadData(int|string $id): JsonResponse
    {
        $lead = $this->findLead($id);

        return response()->json([
            'success' => true,
            'data' => [
                'name'        => $lead->name,
                'phone'       => $lead->phone,
                'email'       => $lead->email,
                'company'     => $lead->company,
                'designation' => $lead->designation,
                'city'        => $lead->city,
                'state'       => $lead->state,
            ],
        ]);
    }
}