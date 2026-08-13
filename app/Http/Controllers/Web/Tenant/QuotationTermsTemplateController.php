<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\QuotationTermsTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationTermsTemplateController extends Controller
{
    private function findTemplate(int|string $id): QuotationTermsTemplate
    {
        return QuotationTermsTemplate::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    public function index(): View
    {
        $templates = QuotationTermsTemplate::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->get();

        return view('tenant.quotation-terms-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        QuotationTermsTemplate::create(array_merge($data, [
            'tenant_id'  => auth()->user()->tenant_id,
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('tenant.quotation-terms-templates.index')
            ->with('success', 'Template saved.');
    }

    public function edit(int|string $id): View
    {
        $template = $this->findTemplate($id);

        return view('tenant.quotation-terms-templates.edit', compact('template'));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $template = $this->findTemplate($id);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $template->update($data);

        return redirect()
            ->route('tenant.quotation-terms-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $template = $this->findTemplate($id);
        $template->delete();

        return redirect()
            ->route('tenant.quotation-terms-templates.index')
            ->with('success', 'Template deleted.');
    }
}
