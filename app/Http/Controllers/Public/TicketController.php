<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TicketNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Unauthenticated, tenant-agnostic controller for the customer-facing
// support form — mirrors Public\AppointmentController's pattern exactly:
// a permanent per-tenant token for new submissions, plus a per-ticket
// token for the customer to track/reply to that one ticket.
class TicketController extends Controller
{
    private function findTenant(string $token): Tenant
    {
        return Tenant::whereJsonContains('settings->support_token', $token)->firstOrFail();
    }

    private function findTicket(string $token): Ticket
    {
        return Ticket::withoutGlobalScope('tenant')
            ->where('public_token', $token)
            ->firstOrFail();
    }

    // ── Submission form ──────────────────────────────────────────────
    public function show(string $token): View
    {
        $tenant = $this->findTenant($token);
        $services = Service::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->active()->orderBy('name')->get(['id', 'name']);

        return view('public.support-show', compact('tenant', 'token', 'services'));
    }

    // ── Create the ticket ─────────────────────────────────────────────
    public function store(Request $request, string $token): RedirectResponse
    {
        $tenant = $this->findTenant($token);

        $data = $request->validate([
            'subject'     => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'service_id'  => ['nullable', 'integer'],
            'name'        => ['required', 'string', 'max:150'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:150'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $contact = Contact::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('phone', $data['phone'])
            ->first();

        if (!$contact) {
            $contact = Contact::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['name'],
                'phone'     => $data['phone'],
                'email'     => $data['email'] ?? null,
            ]);
        }

        $ticket = Ticket::create([
            'tenant_id'     => $tenant->id,
            'ticket_number' => Ticket::generateNumber($tenant->id),
            'contact_id'    => $contact->id,
            'service_id'    => $data['service_id'] ?? null,
            'subject'       => $data['subject'],
            'description'   => $data['description'],
            'status'        => 'open',
            'priority'      => 'medium',
            'source'        => 'public',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->storeAs("tickets/{$tenant->id}/{$ticket->id}", Str::uuid() . '.' . $file->getClientOriginalExtension(), 'public');

            TicketAttachment::create([
                'tenant_id'     => $tenant->id,
                'ticket_id'     => $ticket->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
            ]);
        }

        $this->notifyAdmins($ticket);
        TicketNotificationService::sendConfirmation($ticket->fresh(['contact', 'tenant']));

        return redirect()->route('public.support.ticket', $ticket->ensurePublicToken());
    }

    // ── Customer tracking/reply page ──────────────────────────────────
    public function showTicket(string $token): View
    {
        $ticket = $this->findTicket($token);
        $ticket->load(['contact', 'service', 'attachments']);
        $replies = $ticket->replies()->where('is_internal_note', false)->get();

        return view('public.support-ticket', compact('ticket', 'replies'));
    }

    public function replyAsCustomer(Request $request, string $token): RedirectResponse
    {
        $ticket = $this->findTicket($token);

        if ($ticket->status === 'closed') {
            return back()->with('error', 'This ticket is closed. Please submit a new one if you need further help.');
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $ticket->replies()->create([
            'tenant_id'          => $ticket->tenant_id,
            'author_name'        => $ticket->contact?->name,
            'is_customer_reply'  => true,
            'body'               => $data['body'],
        ]);

        // Reopen if it had been marked resolved and the customer wrote back.
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        $this->notifyReply($ticket);

        return back()->with('success', 'Your reply has been sent.');
    }

    // ── Notifications (staff-facing, via NotificationService) ─────────

    private function notifyAdmins(Ticket $ticket): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('tenant_id', $ticket->tenant_id)
            ->where('user_type', 'tenant_admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            NotificationService::notify(
                'ticket.created',
                $admin,
                ['subject' => $ticket->subject, 'contact_name' => $ticket->contact?->name],
                null,
                route('tenant.tickets.show', $ticket->id),
                $ticket
            );
        }
    }

    private function notifyReply(Ticket $ticket): void
    {
        $recipients = $ticket->assigned_to
            ? User::withoutGlobalScopes()->where('id', $ticket->assigned_to)->get()
            : User::withoutGlobalScopes()->where('tenant_id', $ticket->tenant_id)->where('user_type', 'tenant_admin')->where('is_active', true)->get();

        foreach ($recipients as $user) {
            NotificationService::notify(
                'ticket.customer_replied',
                $user,
                ['subject' => $ticket->subject],
                null,
                route('tenant.tickets.show', $ticket->id),
                $ticket
            );
        }
    }
}
