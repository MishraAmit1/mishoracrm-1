<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

// Public "Talk to sales" flow behind the Enterprise plan on the pricing page.
class ContactSalesController extends Controller
{
    public function show(): View
    {
        return view('contact-sales', [
            'salesEmail'    => PlatformSetting::get('sales_email'),
            'salesPhone'    => PlatformSetting::get('sales_phone'),
            'salesWhatsapp' => PlatformSetting::get('sales_whatsapp'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'company'   => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'max:190'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'team_size' => ['nullable', 'string', 'max:50'],
            'message'   => ['nullable', 'string', 'max:2000'],
        ]);

        $enquiry = ContactEnquiry::create([
            ...$data,
            'status' => 'new',
            'ip'     => $request->ip(),
        ]);

        $this->notifySales($enquiry);

        return back()->with('sales_sent', true);
    }

    // Best-effort email to the configured sales inbox. A mail failure must never
    // block the thank-you — the DB row is the source of truth.
    private function notifySales(ContactEnquiry $enquiry): void
    {
        $to = PlatformSetting::get('sales_email');
        if (!$to) {
            return;
        }

        try {
            $html = view('emails.contact-sales-enquiry', ['enquiry' => $enquiry])->render();

            Mail::send([], [], function ($message) use ($to, $enquiry, $html) {
                $message->to($to)
                        ->subject('New sales enquiry — ' . $enquiry->company)
                        ->html($html);
            });
        } catch (\Throwable $e) {
            Log::error('Contact-sales notification failed: ' . $e->getMessage());
        }
    }
}
