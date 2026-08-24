<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class AppointmentJobService
{
    // ── Start — booked/confirmed → in_progress ─────────────────────
    // Geolocation is a best-effort one-time snapshot (see the Start Work
    // button's JS) — never required, never blocks the action if absent.
    public static function startWork(Appointment $appointment, ?float $lat = null, ?float $lng = null): Appointment
    {
        $appointment->update([
            'status'            => 'in_progress',
            'work_started_at'   => now(),
            'work_started_lat'  => $lat,
            'work_started_lng'  => $lng,
        ]);

        return $appointment;
    }

    // ── Complete — in_progress → completed. Records materials used and
    // decrements stock for any product-linked row via the same atomic
    // Product::adjustStock() used by the Manufacturing module. ─────────
    public static function completeWork(Appointment $appointment, array $materialsUsed, ?float $lat = null, ?float $lng = null): Appointment
    {
        foreach ($materialsUsed as $row) {
            $productId = $row['product_id'] ?? null;
            $qty       = (float) ($row['quantity'] ?? 0);

            if ($productId && $qty > 0) {
                Product::find($productId)?->adjustStock(-$qty);
            }
        }

        $appointment->update([
            'status'              => 'completed',
            'materials_used'      => $materialsUsed,
            'work_completed_at'   => now(),
            'work_completed_lat'  => $lat,
            'work_completed_lng'  => $lng,
        ]);

        return $appointment;
    }

    // ── Customer sign-off — public, token-guarded (see Public\AppointmentController) ──
    public static function signOff(Appointment $appointment, string $signatureData, string $signedName): Appointment
    {
        $appointment->update([
            'customer_signature'    => $signatureData,
            'customer_signed_name'  => $signedName,
            'customer_signed_at'    => now(),
        ]);

        return $appointment;
    }

    // ── Convert a completed job to an Invoice — Service rate + any priced
    // materials as extra line items. Mirrors QuotationService::accept(). ──
    public static function convertToInvoice(Appointment $appointment): Invoice
    {
        if ($appointment->invoice_id) {
            throw ValidationException::withMessages([
                'appointment' => 'An invoice already exists for this job.',
            ]);
        }

        $items = [];

        if ($appointment->service) {
            $items[] = [
                'product_id'   => null,
                'name'         => $appointment->service->name,
                'description'  => $appointment->service->description,
                'quantity'     => 1,
                'rate'         => (float) $appointment->service->rate,
                'tax_percent'  => (float) $appointment->service->tax_percent,
            ];
        }

        foreach ($appointment->materials_used ?? [] as $row) {
            if (empty($row['rate']) || (float) $row['rate'] <= 0) {
                continue; // materials with no rate are treated as included-in-service, not separately billed
            }

            $items[] = [
                'product_id'   => $row['product_id'] ?? null,
                'name'         => $row['name'] ?? 'Material',
                'description'  => null,
                'quantity'     => (float) ($row['quantity'] ?? 1),
                'rate'         => (float) $row['rate'],
                'tax_percent'  => (float) ($row['tax_percent'] ?? 18),
            ];
        }

        $totals = Invoice::calculateTotals($items, 0, 18);

        $invoice = Invoice::create(array_merge($totals, [
            'tenant_id'      => $appointment->tenant_id,
            'contact_id'     => $appointment->contact_id,
            'appointment_id' => $appointment->id,
            'number'         => Invoice::generateNumber($appointment->tenant_id),
            'date'           => now()->toDateString(),
            'due_date'       => now()->addDays(7)->toDateString(),
            'items'          => $items,
            'status'         => 'draft',
            'created_by'     => auth()->id() ?? $appointment->created_by,
        ]));

        $appointment->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }
}
