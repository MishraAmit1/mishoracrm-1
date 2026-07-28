<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InvoicePdfSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InvoicePdfSettingController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    public function index(): View
    {
        $settings  = InvoicePdfSetting::forTenant($this->tenantId());
        $fonts     = InvoicePdfSetting::fonts();
        $tenant    = auth()->user()->tenant;
        $variables = InvoicePdfSetting::variableGroups();

        return view('tenant.invoice-pdf-style.index', compact('settings', 'fonts', 'tenant', 'variables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'primary_color'        => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'         => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family'          => ['required', 'in:' . implode(',', InvoicePdfSetting::fonts())],
            'logo_position'        => ['required', 'in:left,center,right'],
            'footer_note'          => ['nullable', 'string', 'max:500'],
            'show_bank_details'    => ['nullable', 'boolean'],
            'show_tax_summary'     => ['nullable', 'boolean'],
            'logo'                 => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo'          => ['nullable', 'boolean'],
            'use_custom_template'  => ['nullable', 'boolean'],
            'custom_html'          => ['nullable', 'string', 'required_if:use_custom_template,1'],
        ]);

        $settings = InvoicePdfSetting::forTenant($this->tenantId());
        $settings->tenant_id           = $this->tenantId();
        $settings->primary_color       = $request->primary_color;
        $settings->accent_color        = $request->accent_color;
        $settings->font_family         = $request->font_family;
        $settings->logo_position       = $request->logo_position;
        $settings->footer_note         = $request->footer_note;
        $settings->show_bank_details   = $request->boolean('show_bank_details');
        $settings->show_tax_summary    = $request->boolean('show_tax_summary');
        $settings->use_custom_template = $request->boolean('use_custom_template');
        $settings->custom_html         = $request->custom_html;
        $settings->save();

        $tenant = auth()->user()->tenant;

        if ($request->hasFile('logo')) {
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $tenant->logo = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            $tenant->save();
        } elseif ($request->boolean('remove_logo') && $tenant->logo) {
            if (Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $tenant->logo = null;
            $tenant->save();
        }

        return back()->with('success', 'Invoice PDF style saved.');
    }
}
