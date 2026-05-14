<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowupController extends Controller
{
    // ── Staff list helper ─────────────────────────────────────────
    private function getStaffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
                   ->where('is_active', true)
                   ->orderBy('name')
                   ->get(['id', 'name']);
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Followup::with(['lead', 'contact', 'assignedTo'])->latest('scheduled_at');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        // My followups only
        if ($request->boolean('mine')) {
            $query->where('assigned_to', auth()->id());
        }

        $followups = $query->paginate(20)->withQueryString();

        // Counts
        $counts = [
            'all'        => Followup::count(),
            'scheduled'  => Followup::where('status', 'scheduled')->count(),
            'today'      => Followup::today()->count(),
            'overdue'    => Followup::overdue()->count(),
            'done'       => Followup::where('status', 'done')->count(),
            'missed'     => Followup::where('status', 'missed')->count(),
        ];

        $staffList = $this->getStaffList();
        $types     = Followup::types();
        $statuses  = Followup::statuses();

        return view('tenant.followups.index', compact(
            'followups', 'counts', 'staffList', 'types', 'statuses'
        ));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $staffList = $this->getStaffList();
        $types     = Followup::types();

        // Agar lead_id pass hua hai URL se (leads show page se)
        $lead    = $request->filled('lead_id')
                    ? Lead::findOrFail($request->lead_id)
                    : null;

        $contact = $request->filled('contact_id')
                    ? Contact::findOrFail($request->contact_id)
                    : null;

        // Lead aur contact dropdown ke liye
        $leads    = Lead::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts = Contact::orderBy('name')->get(['id', 'name', 'phone']);

        return view('tenant.followups.create', compact(
            'staffList', 'types', 'lead', 'contact', 'leads', 'contacts'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'lead_id'      => ['nullable', 'exists:leads,id'],
            'contact_id'   => ['nullable', 'exists:contacts,id'],
            'assigned_to'  => ['required', 'exists:users,id'],
            'type'         => ['required', 'in:call,email,whatsapp,meeting,other'],
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        Followup::create([
            'tenant_id'    => auth()->user()->tenant_id,
            'lead_id'      => $request->lead_id,
            'contact_id'   => $request->contact_id,
            'assigned_to'  => $request->assigned_to,
            'created_by'   => auth()->id(),
            'type'         => $request->type,
            'scheduled_at' => $request->scheduled_at,
            'notes'        => $request->notes,
            'status'       => 'scheduled',
        ]);

        // Redirect back to lead/contact if came from there
        if ($request->filled('lead_id')) {
            return redirect()
                ->route('tenant.leads.show', $request->lead_id)
                ->with('success', 'Follow-up scheduled.');
        }

        if ($request->filled('contact_id')) {
            return redirect()
                ->route('tenant.contacts.show', $request->contact_id)
                ->with('success', 'Follow-up scheduled.');
        }

        return redirect()
            ->route('tenant.followups.index')
            ->with('success', 'Follow-up scheduled successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(Followup $followup): View
    {
        $followup->load(['lead', 'contact', 'assignedTo', 'createdBy']);

        return view('tenant.followups.show', compact('followup'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(Followup $followup)
    {
        $staffList = $this->getStaffList();
        $types     = Followup::types();
        $leads     = Lead::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'phone']);
  

        return view('tenant.followups.edit', compact(
            'followup', 'staffList', 'types', 'leads', 'contacts'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Followup $followup): RedirectResponse
    {
        $request->validate([
            'lead_id'      => ['nullable', 'exists:leads,id'],
            'contact_id'   => ['nullable', 'exists:contacts,id'],
            'assigned_to'  => ['required', 'exists:users,id'],
            'type'         => ['required', 'in:call,email,whatsapp,meeting,other'],
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'status'       => ['required', 'in:scheduled,done,missed,rescheduled'],
            'outcome'      => ['nullable', 'string', 'max:2000'],
        ]);

        $data = $request->only([
            'lead_id', 'contact_id', 'assigned_to',
            'type', 'scheduled_at', 'notes', 'status', 'outcome',
        ]);

        if ($request->status === 'done' && !$followup->done_at) {
            $data['done_at'] = now();
        }

        $followup->update($data);

        return redirect()
            ->route('tenant.followups.show', $followup)
            ->with('success', 'Follow-up updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(Followup $followup): RedirectResponse
    {
        $leadId    = $followup->lead_id;
        $contactId = $followup->contact_id;

        $followup->delete();

        if ($leadId) {
            return redirect()
                ->route('tenant.leads.show', $leadId)
                ->with('success', 'Follow-up deleted.');
        }

        return redirect()
            ->route('tenant.followups.index')
            ->with('success', 'Follow-up deleted.');
    }

    // ── Mark Done ─────────────────────────────────────────────────
    public function markDone(Request $request, Followup $followup): RedirectResponse
    {
        $request->validate([
            'outcome' => ['nullable', 'string', 'max:2000'],
        ]);

        $followup->update([
            'status'  => 'done',
            'done_at' => now(),
            'outcome' => $request->outcome,
        ]);

        return back()->with('success', 'Follow-up marked as done.');
    }

    // ── Mark Missed ───────────────────────────────────────────────
    public function markMissed(Followup $followup): RedirectResponse
    {
        $followup->update(['status' => 'missed']);

        return back()->with('success', 'Follow-up marked as missed.');
    }
}