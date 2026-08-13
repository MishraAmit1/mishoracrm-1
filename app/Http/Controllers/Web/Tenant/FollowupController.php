<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Followup;
use App\Models\FollowupAttachment;
use App\Models\Lead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FollowupController extends Controller
{
    // ── Staff list helper ─────────────────────────────────────────
    private function getStaffList()
    {
        return User::where('tenant_id', Auth::user()->tenant_id)
                   ->where('is_active', true)
                   ->orderBy('name')
                   ->get(['id', 'name']);
    }

    // ── Attachment upload rules ─────────────────────────────────────
    private function attachmentRules(): array
    {
        return [
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file', 'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            ],
        ];
    }

    // ── Save uploaded attachment files against a follow-up ──────────
    private function saveAttachments(Request $request, Followup $followup): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $tenantId = Auth::user()->tenant_id;

        foreach ($request->file('attachments') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs("followups/{$tenantId}/{$followup->id}", $filename, 'public');

            FollowupAttachment::create([
                'tenant_id'     => $tenantId,
                'followup_id'   => $followup->id,
                'uploaded_by'   => Auth::id(),
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Followup::with(['lead', 'contact', 'assignedTo'])->latest('scheduled_at');
        ViewScope::apply($query, 'followups', $user);

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

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // My followups only
        if ($request->boolean('mine')) {
            $query->where('assigned_to', Auth::id());
        }

        $followups = $query->paginate(20)->withQueryString();

        // Counts — scoped the same way as the listing, so a "view_own" user
        // never sees totals that reveal other staff members' follow-up counts.
        $countsBase = fn () => ViewScope::apply(Followup::query(), 'followups', $user);
        $counts = [
            'all'        => $countsBase()->count(),
            'scheduled'  => $countsBase()->where('status', 'scheduled')->count(),
            'today'      => $countsBase()->today()->count(),
            'overdue'    => $countsBase()->overdue()->count(),
            'done'       => $countsBase()->where('status', 'done')->count(),
            'missed'     => $countsBase()->where('status', 'missed')->count(),
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
        $this->authorize('create', Followup::class);

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
        $this->authorize('create', Followup::class);

        $request->validate(array_merge([
            'lead_id'      => ['nullable', 'exists:leads,id'],
            'contact_id'   => ['nullable', 'exists:contacts,id'],
            'assigned_to'  => ['required', 'exists:users,id'],
            'type'         => ['required', 'in:call,email,whatsapp,meeting,other'],
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ], $this->attachmentRules()));

        $followup = Followup::create([
            'tenant_id'    => Auth::user()->tenant_id,
            'lead_id'      => $request->lead_id,
            'contact_id'   => $request->contact_id,
            'assigned_to'  => $request->assigned_to,
            'created_by'   => Auth::id(),
            'type'         => $request->type,
            'scheduled_at' => $request->scheduled_at,
            'notes'        => $request->notes,
            'status'       => 'scheduled',
        ]);

        $this->saveAttachments($request, $followup);
        $this->notifyFollowupScheduled($followup);

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
        $this->authorize('view', $followup);

        $followup->load(['lead', 'contact', 'assignedTo', 'createdBy', 'attachments.uploadedBy']);

        return view('tenant.followups.show', compact('followup'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(Followup $followup)
    {
        $this->authorize('update', $followup);

        $staffList = $this->getStaffList();
        $types     = Followup::types();
        $leads     = Lead::orderBy('name')->get(['id', 'name', 'phone']);
        $contacts  = Contact::orderBy('name')->get(['id', 'name', 'phone']);
        $followup->load('attachments.uploadedBy');

        return view('tenant.followups.edit', compact(
            'followup', 'staffList', 'types', 'leads', 'contacts'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Followup $followup): RedirectResponse
    {
        $this->authorize('update', $followup);

        $request->validate(array_merge([
            'lead_id'      => ['nullable', 'exists:leads,id'],
            'contact_id'   => ['nullable', 'exists:contacts,id'],
            'assigned_to'  => ['required', 'exists:users,id'],
            'type'         => ['required', 'in:call,email,whatsapp,meeting,other'],
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'status'       => ['required', 'in:scheduled,done,missed,rescheduled'],
            'outcome'      => ['nullable', 'string', 'max:2000'],
        ], $this->attachmentRules()));

        $data = $request->only([
            'lead_id', 'contact_id', 'assigned_to',
            'type', 'scheduled_at', 'notes', 'status', 'outcome',
        ]);

        if ($request->status === 'done' && !$followup->done_at) {
            $data['done_at'] = now();
        }

        $originalAssignedTo   = $followup->assigned_to;
        $originalScheduledAt  = $followup->scheduled_at?->format('Y-m-d H:i:s');
        $nextAssignedTo       = $data['assigned_to'];
        $nextScheduledAt      = $data['scheduled_at'];

        $followup->update($data);

        $this->saveAttachments($request, $followup);

        if ($followup->status === 'scheduled' && (
            $nextAssignedTo !== $originalAssignedTo ||
            $nextScheduledAt !== $originalScheduledAt
        )) {
            $this->notifyFollowupScheduled($followup);
        }

        return redirect()
            ->route('tenant.followups.show', $followup)
            ->with('success', 'Follow-up updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(Followup $followup): RedirectResponse
    {
        $this->authorize('delete', $followup);

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
        $this->authorize('update', $followup);

        $request->validate(array_merge([
            'outcome' => ['nullable', 'string', 'max:2000'],
        ], $this->attachmentRules()));

        $followup->update([
            'status'  => 'done',
            'done_at' => now(),
            'outcome' => $request->outcome,
        ]);

        $this->saveAttachments($request, $followup);

        return back()->with('success', 'Follow-up marked as done.');
    }

    // ── Mark Missed ───────────────────────────────────────────────
    public function markMissed(Followup $followup): RedirectResponse
    {
        $this->authorize('update', $followup);

        $followup->update(['status' => 'missed']);

        return back()->with('success', 'Follow-up marked as missed.');
    }

    private function notifyFollowupScheduled(Followup $followup): void
    {
        if (! $followup->assignedTo) {
            return;
        }

        $entityName = $followup->lead?->name
            ?? $followup->contact?->name
            ?? 'customer';

        NotificationService::notify(
            'followup.scheduled',
            $followup->assignedTo,
            [
                'name' => $entityName,
                'date' => $followup->scheduled_at?->format('d M Y, h:i A') ?? '',
            ],
            Auth::user(),
            route('tenant.followups.show', $followup),
            $followup
        );
    }

    // ── Attachments: Upload ──────────────────────────────────────
    public function storeAttachment(Request $request, Followup $followup): RedirectResponse
    {
        $this->authorize('update', $followup);

        $request->validate(array_merge(
            ['attachments' => ['required', 'array', 'max:5']],
            $this->attachmentRules()
        ));

        $this->saveAttachments($request, $followup);

        return back()->with('success', 'Attachment(s) uploaded successfully.');
    }

    // ── Attachments: Delete ──────────────────────────────────────
    public function destroyAttachment(Followup $followup, FollowupAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', $followup);

        abort_unless($attachment->followup_id === $followup->id, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment deleted.');
    }
}