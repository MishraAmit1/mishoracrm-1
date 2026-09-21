<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentJobService;
use App\Services\CustomerLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// Unauthenticated, tenant-agnostic controller for the customer-facing
// booking link. Access control is the unguessable per-tenant booking_token
// (and, for managing one's own booking, a separate per-appointment
// public_token) — not auth/tenant middleware, same pattern as the
// Public\QuotationController.
class AppointmentController extends Controller
{
    private function findTenant(string $token): Tenant
    {
        return Tenant::whereJsonContains('settings->booking_token', $token)->firstOrFail();
    }

    private function findAppointment(string $token): Appointment
    {
        return Appointment::withoutGlobalScope('tenant')
            ->where('public_token', $token)
            ->firstOrFail();
    }

    // ── Booking landing page — pick a service ───────────────────────
    public function show(string $token): View
    {
        $tenant = $this->findTenant($token);
        $enabled = $tenant->bookingSettings()['enabled'];

        $services = $enabled
            ? Service::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->active()->bookable()->orderBy('name')->get()
            : collect();

        return view('public.booking-show', compact('tenant', 'token', 'enabled', 'services'));
    }

    // ── Available slots for a service + date (AJAX) ─────────────────
    public function slots(Request $request, string $token): JsonResponse
    {
        $tenant = $this->findTenant($token);

        $request->validate([
            'service_id' => ['required', 'integer'],
            'date'       => ['required', 'date'],
        ]);

        $service = Service::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)->bookable()->active()
            ->find($request->service_id);

        if (!$service) {
            return response()->json(['slots' => []]);
        }

        $date  = Carbon::parse($request->date);
        $slots = Appointment::availableSlots($tenant, $date, (int) $tenant->bookingSettings()['slot_duration_minutes']);

        return response()->json(['slots' => $slots]);
    }

    // ── Create the booking ───────────────────────────────────────────
    public function store(Request $request, string $token): RedirectResponse
    {
        $tenant = $this->findTenant($token);

        if (!$tenant->bookingSettings()['enabled']) {
            return back()->with('error', 'Online booking is currently unavailable.');
        }

        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'date'       => ['required', 'date'],
            'time'       => ['required', 'date_format:H:i'],
            'name'       => ['required', 'string', 'max:150'],
            'phone'      => ['required', 'string', 'max:20'],
            'email'      => ['nullable', 'email', 'max:150'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $service = Service::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)->bookable()->active()
            ->findOrFail($data['service_id']);

        $duration = $tenant->bookingSettings()['slot_duration_minutes'];
        $startsAt = Carbon::parse($data['date'] . ' ' . $data['time']);
        $endsAt   = $startsAt->copy()->addMinutes((int) $duration);

        // Re-check availability at submit time — a slot shown as free in
        // the browser may have been taken by someone else in the meantime.
        $stillAvailable = in_array($startsAt->format('H:i'), Appointment::availableSlots($tenant, $startsAt->copy()->startOfDay(), (int) $duration), true);
        if (!$stillAvailable) {
            return back()->withInput()->with('error', 'Sorry, that slot was just taken. Please pick another time.');
        }

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

            app(CustomerLinkService::class)->attachContactToCustomer($contact);
        }

        $appointment = Appointment::create([
            'tenant_id'  => $tenant->id,
            'contact_id' => $contact->id,
            'service_id' => $service->id,
            'starts_at'  => $startsAt,
            'ends_at'    => $endsAt,
            'status'     => 'booked',
            'source'     => 'public',
            'notes'      => $data['notes'] ?? null,
        ]);

        AppointmentJobService::sendBookingConfirmation($appointment);

        return redirect()->route('public.booking.appointment', $appointment->ensurePublicToken());
    }

    // ── Manage-my-booking page ───────────────────────────────────────
    public function showAppointment(string $token): View
    {
        $appointment = $this->findAppointment($token);
        $appointment->load(['contact', 'service', 'tenant', 'assignedTo', 'attachments']);

        return view('public.booking-confirmed', compact('appointment'));
    }

    public function cancelByCustomer(string $token): RedirectResponse
    {
        $appointment = $this->findAppointment($token);

        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return back()->with('info', 'This booking is no longer active.');
        }

        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Your booking has been cancelled.');
    }

    // ── Customer sign-off on a completed job ──────────────────────────
    public function signOff(Request $request, string $token): RedirectResponse
    {
        $appointment = $this->findAppointment($token);

        if ($appointment->status !== 'completed') {
            return back()->with('error', 'This job is not yet marked completed.');
        }

        if ($appointment->customer_signed_at) {
            return back()->with('info', 'This job has already been signed off.');
        }

        $data = $request->validate([
            'signed_name'    => ['required', 'string', 'max:150'],
            'signature_data' => ['required', 'string'],
        ]);

        AppointmentJobService::signOff($appointment, $data['signature_data'], $data['signed_name']);

        return back()->with('success', 'Thank you — your sign-off has been recorded.');
    }
}
