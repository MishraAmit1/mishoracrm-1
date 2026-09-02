<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $enquiries = ContactEnquiry::query()
            ->when(\in_array($status, ContactEnquiry::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all'       => ContactEnquiry::count(),
            'new'       => ContactEnquiry::where('status', 'new')->count(),
            'contacted' => ContactEnquiry::where('status', 'contacted')->count(),
            'closed'    => ContactEnquiry::where('status', 'closed')->count(),
        ];

        return view('superadmin.contact-enquiries.index', [
            'enquiries'     => $enquiries,
            'counts'        => $counts,
            'activeStatus'  => $status,
            'salesEmail'    => PlatformSetting::get('sales_email'),
            'salesPhone'    => PlatformSetting::get('sales_phone'),
            'salesWhatsapp' => PlatformSetting::get('sales_whatsapp'),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_email'    => ['nullable', 'email', 'max:190'],
            'sales_phone'    => ['nullable', 'string', 'max:30'],
            'sales_whatsapp' => ['nullable', 'string', 'max:30'],
        ]);

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, $value ?? '');
        }

        return back()->with('success', 'Sales contact details saved.');
    }

    public function updateStatus(Request $request, ContactEnquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $enquiry->update([
            'status'     => $data['status'],
            'handled_by' => $data['status'] === 'new' ? null : Auth::id(),
            'handled_at' => $data['status'] === 'new' ? null : now(),
        ]);

        return back()->with('success', 'Enquiry marked as ' . $data['status'] . '.');
    }

    public function destroy(ContactEnquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return back()->with('success', 'Enquiry deleted.');
    }
}
