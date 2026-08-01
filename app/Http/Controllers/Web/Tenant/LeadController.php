<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Contact;
use App\Models\CustomFieldValue;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LeadCallLog;
use App\Models\TenantFieldAssignment;
use App\Models\User;
use App\Services\DuplicateMatcher;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findLead(int|string $id): Lead
    {
        return Lead::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function getStaffList()
    {
        return User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function getCustomFields(): \Illuminate\Support\Collection
    {
        return TenantFieldAssignment::getActiveFields($this->tenantId(), 'lead');
    }

    private function validateAssignee($assigneeId): ?int
    {
        if (empty($assigneeId)) return null;

        return User::where('id', $assigneeId)
            ->where('tenant_id', $this->tenantId())
            ->exists() ? (int) $assigneeId : null;
    }

    // ── Shared filtered query (index page + export reuse this) ────
    public static function filteredQuery(int $tenantId, array $filters, bool $isAdmin, ?int $currentUserId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Lead::where('tenant_id', $tenantId);

        if (!$isAdmin && $currentUserId) {
            $query->where('assigned_to', $currentUserId);
        }

        if (!empty($filters['search']))      $query->search($filters['search']);
        if (!empty($filters['status']))      $query->where('status',      $filters['status']);
        if (!empty($filters['source']))      $query->where('source',      $filters['source']);
        if (!empty($filters['priority']))    $query->where('priority',    $filters['priority']);
        if (!empty($filters['assigned_to'])) $query->where('assigned_to', $filters['assigned_to']);
        if (!empty($filters['date_from']))   $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))     $query->whereDate('created_at', '<=', $filters['date_to']);

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $user    = auth()->user();
        $isAdmin = $user->user_type === 'tenant_admin';

        $query = self::filteredQuery($this->tenantId(), $request->all(), $isAdmin, $user->id)
            ->with(['assignedTo'])
            ->withCount(['followups']);

        $allowed = ['name', 'created_at', 'status', 'priority', 'source'];
        $sort    = in_array($request->get('sort'), $allowed) ? $request->get('sort') : 'created_at';
        $dir     = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $leads = $query->paginate(20)->withQueryString();

        $base = fn() => Lead::where('tenant_id', $this->tenantId())
            ->when(!$isAdmin, fn($q) => $q->where('assigned_to', $user->id));

        $counts = [
            'all'       => $base()->count(),
            'new'       => $base()->where('status', 'new')->count(),
            'contacted' => $base()->where('status', 'contacted')->count(),
            'qualified' => $base()->where('status', 'qualified')->count(),
            'converted' => $base()->where('status', 'converted')->count(),
            'lost'      => $base()->where('status', 'lost')->count(),
        ];

        return view('tenant.leads.index', [
            'leads'      => $leads,
            'counts'     => $counts,
            'staffList'  => $this->getStaffList(),
            'sources'    => Lead::sources(),
            'statuses'   => Lead::statuses(),
            'priorities' => Lead::priorities(),
            'isAdmin'    => $isAdmin,
        ]);
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        return view('tenant.leads.create', [
            'staffList'    => $this->getStaffList(),
            'sources'      => Lead::sources(),
            'statuses'     => Lead::statuses(),
            'priorities'   => Lead::priorities(),
            'customFields' => $this->getCustomFields(),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(LeadRequest $request): RedirectResponse
    {
        $leadData = $request->leadData();
        $leadData['assigned_to'] = $this->validateAssignee($leadData['assigned_to'] ?? null);

        $lead = Lead::create(array_merge($leadData, [
            'tenant_id'  => $this->tenantId(),
            'created_by' => auth()->id(),
        ]));

        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        WebhookService::fire('lead.created', $lead->tenant_id, [
            'id'     => $lead->id,
            'name'   => $lead->name,
            'phone'  => $lead->phone,
            'email'  => $lead->email,
            'source' => $lead->source,
            'status' => $lead->status,
        ]);

        return redirect()
            ->route('tenant.leads.show', $lead->id)
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
            'contact',
            'deal',
            'callLogs.createdBy',
            'emailLogs.sentBy',
            'whatsappLogs.sentBy',
        ]);

        return view('tenant.leads.show', [
            'lead'         => $lead,
            'staffList'    => $this->getStaffList(),
            'sources'      => Lead::sources(),
            'statuses'     => Lead::statuses(),
            'priorities'   => Lead::priorities(),
            'customFields' => $this->getCustomFields(),
            'customValues' => CustomFieldValue::getByKeyForModel($lead),
            'isAdmin'      => auth()->user()->user_type === 'tenant_admin',
            'timeline'     => \App\Services\ActivityTimelineService::forLead($lead),
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $lead = $this->findLead($id);

        return view('tenant.leads.edit', [
            'lead'         => $lead,
            'staffList'    => $this->getStaffList(),
            'sources'      => Lead::sources(),
            'statuses'     => Lead::statuses(),
            'priorities'   => Lead::priorities(),
            'customFields' => $this->getCustomFields(),
            'customValues' => CustomFieldValue::getByAssignmentForModel($lead),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(LeadRequest $request, int|string $id): RedirectResponse
    {
        $lead     = $this->findLead($id);
        $leadData = $request->leadData();

        $leadData['assigned_to'] = $this->validateAssignee($leadData['assigned_to'] ?? null);

        // contacted_at timestamp
        if ($request->status === 'contacted' && $lead->status !== 'contacted') {
            $leadData['contacted_at'] = now();
        }

        // converted_at — sirf convert() method se hoga, directly nahi
        // agar koi status manually 'converted' pe set kare toh bhi handle karo
        if ($request->status === 'converted' && $lead->status !== 'converted') {
            $leadData['converted_at'] = now();
        }

        $lead->update($leadData);
        $this->saveCustomFields($lead, $request->input('custom_fields', []));

        if ($lead->wasChanged('status') && !$lead->wasChanged(['name', 'phone', 'email'])) {
            return back()->with('success', 'Lead status updated.');
        }

        return redirect()
            ->route('tenant.leads.show', $lead->id)
            ->with('success', 'Lead updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $lead = $this->findLead($id);

        if ($lead->isConverted()) {
            return back()->with('error', 'Converted lead cannot be deleted.');
        }

        $name = $lead->name;
        $lead->customFieldValues()->delete();
        $lead->delete();

        return redirect()
            ->route('tenant.leads.index')
            ->with('success', "Lead '{$name}' deleted.");
    }

    // ── Update Status (PATCH for Kanban) ──────────────────────────
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:new,contacted,qualified,converted,lost'],
        ]);

        $lead = $this->findLead($id);

        if ($request->status === 'converted') {
            $this->convert($lead->id);
            // return response()->json([
            //     'ok'       => true,
            //     'redirect' => route('tenant.leads.convert', $lead->id),
            // ]);
        }

        $data = ['status' => $request->status];

        if ($request->status === 'contacted' && $lead->status !== 'contacted') {
            $data['contacted_at'] = now();
        }

        $oldStatus = $lead->status;
        $lead->update($data);

        if ($oldStatus !== $lead->status) {
            WebhookService::fire('lead.status_changed', $lead->tenant_id, [
                'id'         => $lead->id,
                'name'       => $lead->name,
                'phone'      => $lead->phone,
                'old_status' => $oldStatus,
                'new_status' => $lead->status,
            ]);
        }

        return response()->json(['ok' => true, 'status' => $lead->status]);
    }

    // ── Save view preference ──────────────────────────────────────
    public function saveView(Request $request): JsonResponse
    {
        session(['lead_view' => $request->view === 'kanban' ? 'kanban' : 'list']);
        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // CONVERT TO CONTACT
    //
    // Flow:
    //   1. Lead → Contact (saari details copy hoti hain)
    //   2. Contact ke saath ek Deal automatically banta hai
    //   3. Lead status → converted, converted_at save hota hai
    //   4. Lead aur Contact dono linked rehte hain (contact_id)
    //   5. Deal lead_id se bhi linked hota hai
    //
    // Agar deal_value lead model mein hai toh Deal mein value bhi set hoti hai
    // ─────────────────────────────────────────────────────────────
    public function convert(int|string $id): RedirectResponse
    {
        $lead = $this->findLead($id);

        // Already converted check
        if ($lead->isConverted()) {
            // Contact relation se check karo
            $existingContact = Contact::where('lead_id', $lead->id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if ($existingContact) {
                return redirect()
                    ->route('tenant.contacts.show', $existingContact->id)
                    ->with('info', 'This lead is already converted.');
            }
            return back()->with('error', 'Lead is already converted.');
        }

        // ── Contact + Deal create karo, lead ko converted mark karo ──
        $contact = $lead->convertToContact();

        return redirect()
            ->route('tenant.contacts.show', $contact->id)
            ->with('success', "Lead '{$lead->name}' converted to contact successfully.");
    }

    // ── Assign ────────────────────────────────────────────────────
    public function assign(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['assigned_to' => ['required', 'exists:users,id']]);

        $lead  = $this->findLead($id);
        $valid = User::where('id', $request->assigned_to)
            ->where('tenant_id', $this->tenantId())
            ->exists();

        if (!$valid) return back()->with('error', 'Invalid staff member.');

        $lead->update(['assigned_to' => $request->assigned_to]);
        return back()->with('success', 'Lead assigned.');
    }

    // ── Update Status (form submit) ───────────────────────────────
    public function updateStatusForm(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'status'      => ['required', 'in:new,contacted,qualified,converted,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $lead = $this->findLead($id);
        $data = ['status' => $request->status];

        // Block direct 'converted' status change — use convert() instead
        if ($request->status === 'converted') {
            return redirect()
                ->route('tenant.leads.convert', $lead->id);
        }

        if ($request->status === 'contacted') $data['contacted_at'] = now();
        if ($request->status === 'lost' && $request->filled('lost_reason')) {
            $data['lost_reason'] = $request->lost_reason;
        }

        $lead->update($data);
        return back()->with('success', 'Status updated.');
    }

    // ── Bulk update status ────────────────────────────────────────
    public function bulkUpdateStatus(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('leads.edit_all') || $request->user()->can('leads.edit_own'), 403);

        $request->validate([
            'ids'         => ['required', 'array'],
            'ids.*'       => ['exists:leads,id'],
            'status'      => ['required', 'in:new,contacted,qualified,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'contacted') $data['contacted_at'] = now();
        if ($request->status === 'lost' && $request->filled('lost_reason')) {
            $data['lost_reason'] = $request->lost_reason;
        }

        $count = Lead::whereIn('id', $request->ids)
            ->where('tenant_id', $this->tenantId())
            ->update($data);

        if ($request->wantsJson()) {
            return response()->json(['updated' => $count]);
        }

        return back()->with('success', 'Statuses updated.');
    }

    // ── Bulk delete ─────────────────────────────────────────────────
    public function bulkDestroy(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('leads.delete'), 403);

        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['exists:leads,id'],
        ]);

        $leads = Lead::whereIn('id', $request->ids)
            ->where('tenant_id', $this->tenantId())
            ->get();

        $deleted = 0;
        $skippedConverted = 0;

        foreach ($leads as $lead) {
            if ($lead->isConverted()) {
                $skippedConverted++;
                continue;
            }
            $lead->customFieldValues()->delete();
            $lead->delete();
            $deleted++;
        }

        return response()->json(['deleted' => $deleted, 'skipped_converted' => $skippedConverted]);
    }

    // ── Bulk assign ──────────────────────────────────────────────────
    public function bulkAssign(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('leads.assign'), 403);

        $request->validate([
            'ids'         => ['required', 'array'],
            'ids.*'       => ['exists:leads,id'],
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $valid = User::where('id', $request->assigned_to)
            ->where('tenant_id', $this->tenantId())
            ->exists();

        if (!$valid) {
            return response()->json(['error' => 'Invalid staff member.'], 422);
        }

        $count = Lead::whereIn('id', $request->ids)
            ->where('tenant_id', $this->tenantId())
            ->update(['assigned_to' => $request->assigned_to]);

        return response()->json(['assigned' => $count]);
    }

    // ── Lead data for Contact/Deal pre-fill (API) ─────────────────
    public function leadData(int|string $id): JsonResponse
    {
        $lead = $this->findLead($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'name'        => $lead->name,
                'phone'       => $lead->phone,
                'email'       => $lead->email,
                'company'     => $lead->company,
                'designation' => $lead->designation,
                'city'        => $lead->city,
                'state'       => $lead->state,
                'lead_value'  => $lead->lead_value,
                'source'      => $lead->source,
            ],
        ]);
    }

    // ── Store Call Log / Note ─────────────────────────────────────
    public function storeCallLog(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->tenant_id === $this->tenantId(), 403);

        $request->validate([
            'type'          => ['required', 'in:call,note,email,meeting,whatsapp'],
            'description'   => ['required', 'string', 'max:2000'],
            'call_outcome'  => ['nullable', 'in:connected,no_answer,voicemail,callback,not_interested'],
            'call_duration' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        LeadCallLog::create([
            'tenant_id'     => $this->tenantId(),
            'lead_id'       => $lead->id,
            'type'          => $request->type,
            'description'   => $request->description,
            'call_outcome'  => $request->type === 'call' ? $request->call_outcome : null,
            'call_duration' => $request->type === 'call' ? $request->call_duration : null,
            'logged_at'     => now(),
            'created_by'    => auth()->id(),
        ]);

        return back()->with('success', ucfirst($request->type) . ' logged successfully.');
    }

    // ── Live duplicate check (Add/Edit forms) ─────────────────────
    public function checkDuplicate(Request $request): JsonResponse
    {
        $match = DuplicateMatcher::findExistingLead(
            $this->tenantId(),
            $request->input('phone'),
            $request->input('email'),
            $request->integer('except_id') ?: null
        );

        return response()->json([
            'duplicate' => (bool) $match,
            'match'     => $match ? ['id' => $match->id, 'name' => $match->name] : null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // saveCustomFields — field_key + assignment_id upsert
    // ─────────────────────────────────────────────────────────────
    private function saveCustomFields(Lead $lead, array $data): void
    {
        if (empty($data)) return;

        $assignments = TenantFieldAssignment::where('tenant_id', $this->tenantId())
            ->where('module', 'lead')
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

            $value = match($fieldType) {
                'multi_select' => json_encode(
                    is_array($value) ? array_values(array_filter($value)) : []
                ),
                'checkbox' => ($value && $value !== '0') ? '1' : '0',
                'number'   => is_numeric($value) ? $value : null,
                default    => is_string($value) ? trim($value) : (string)($value ?? ''),
            };

            if (($value === '' || is_null($value)) && !($fieldInfo['is_required'] ?? false)) {
                CustomFieldValue::where('tenant_id',    $this->tenantId())
                    ->where('model_type', Lead::class)
                    ->where('model_id',   $lead->id)
                    ->where('field_key',  $fieldKey)
                    ->delete();
                continue;
            }

            CustomFieldValue::updateOrCreate(
                [
                    'tenant_id'  => $this->tenantId(),
                    'model_type' => Lead::class,
                    'model_id'   => $lead->id,
                    'field_key'  => $fieldKey,
                ],
                [
                    'value'         => $value ?? '',
                    'assignment_id' => (int) $assignmentId,
                ]
            );
        }
    }
}