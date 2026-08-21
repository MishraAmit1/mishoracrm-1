<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findAppointment(int|string $id): Appointment
    {
        return Appointment::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Appointment::with(['contact', 'service'])
            ->where('tenant_id', $this->tenantId())
            ->latest('starts_at');

        $status = $request->get('status', 'upcoming');

        match ($status) {
            'upcoming'  => $query->upcoming(),
            'today'     => $query->today()->active(),
            'completed' => $query->where('status', 'completed'),
            'cancelled' => $query->cancelled(),
            default     => null,
        };

        $appointments = $query->paginate(20)->withQueryString();

        $base = Appointment::where('tenant_id', $this->tenantId());
        $counts = [
            'upcoming'  => (clone $base)->upcoming()->count(),
            'today'     => (clone $base)->today()->active()->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->cancelled()->count(),
        ];

        $tenant = auth()->user()->tenant;

        return view('tenant.appointments.index', compact('appointments', 'counts', 'status', 'tenant'));
    }

    // ── Create (manual booking by staff) ─────────────────────────────
    public function create(): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company', 'phone']);
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'rate']);
        $tenant   = auth()->user()->tenant;

        return view('tenant.appointments.create', compact('contacts', 'services', 'tenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date'       => ['required', 'date'],
            'time'       => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = Carbon::parse($data['date'] . ' ' . $data['time']);

        Appointment::create([
            'tenant_id'  => $this->tenantId(),
            'contact_id' => $data['contact_id'],
            'service_id' => $data['service_id'] ?? null,
            'starts_at'  => $startsAt,
            'ends_at'    => $startsAt->copy()->addMinutes((int) $data['duration_minutes']),
            'status'     => 'confirmed',
            'source'     => 'manual',
            'notes'      => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('tenant.appointments.index')
            ->with('success', 'Appointment booked.');
    }

    // ── Status actions ────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:booked,confirmed,completed,cancelled,no_show']]);

        $appointment = $this->findAppointment($id);
        $appointment->update(['status' => $request->status]);

        return back()->with('success', 'Appointment marked ' . (Appointment::statuses()[$request->status] ?? $request->status) . '.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $this->findAppointment($id)->delete();

        return redirect()->route('tenant.appointments.index')
            ->with('success', 'Appointment deleted.');
    }

    // ── JSON — available slots for the manual-booking form ──────────
    public function slots(Request $request)
    {
        $request->validate(['date' => ['required', 'date'], 'duration_minutes' => ['required', 'integer', 'min:5']]);

        $tenant = auth()->user()->tenant;
        $slots  = Appointment::availableSlots($tenant, Carbon::parse($request->date), (int) $request->duration_minutes);

        return response()->json(['slots' => $slots]);
    }

    // ── Booking Settings ──────────────────────────────────────────
    public function settings(): View
    {
        $tenant = auth()->user()->tenant;
        $settings = $tenant->bookingSettings();
        $bookableServices = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'is_bookable']);

        return view('tenant.appointments.settings', compact('tenant', 'settings', 'bookableServices'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled'               => ['nullable', 'boolean'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'capacity_per_slot'     => ['required', 'integer', 'min:1', 'max:100'],
            'advance_booking_days'  => ['required', 'integer', 'min:1', 'max:365'],
            'hours'                 => ['required', 'array'],
            'bookable_service_ids'  => ['nullable', 'array'],
            'bookable_service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        $tenant   = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];

        $hours = [];
        foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day) {
            $hours[$day] = [
                'closed' => empty($data['hours'][$day]['closed']) ? false : true,
                'open'   => $data['hours'][$day]['open']  ?? '09:00',
                'close'  => $data['hours'][$day]['close'] ?? '18:00',
            ];
        }

        $settings['booking'] = [
            'enabled'               => $request->boolean('enabled'),
            'slot_duration_minutes' => (int) $data['slot_duration_minutes'],
            'capacity_per_slot'     => (int) $data['capacity_per_slot'],
            'advance_booking_days'  => (int) $data['advance_booking_days'],
            'hours'                 => $hours,
        ];

        $tenant->update(['settings' => $settings]);

        // Sync which Services are bookable
        Service::where('tenant_id', $this->tenantId())->update(['is_bookable' => false]);
        if (!empty($data['bookable_service_ids'])) {
            Service::where('tenant_id', $this->tenantId())
                ->whereIn('id', $data['bookable_service_ids'])
                ->update(['is_bookable' => true]);
        }

        return back()->with('success', 'Booking settings updated.');
    }
}
