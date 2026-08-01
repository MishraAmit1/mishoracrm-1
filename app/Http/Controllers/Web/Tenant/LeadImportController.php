<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Exports\LeadsExport;
use App\Http\Controllers\Controller;
use App\Services\Import\LeadImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LeadImportController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function importDir(): string
    {
        return 'imports/' . $this->tenantId();
    }

    // ── Step 1: upload form ────────────────────────────────────────
    public function show(): View
    {
        return view('tenant.leads.import');
    }

    // ── Step 2: read headers, render column-mapping screen ─────────
    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $filename = Str::uuid() . '.' . $request->file('file')->getClientOriginalExtension();
        $request->file('file')->storeAs($this->importDir(), $filename, 'local');

        $path = Storage::disk('local')->path($this->importDir() . '/' . $filename);

        $sheets = Excel::toCollection(null, $path);
        $rows   = $sheets->first() ?? collect();

        if ($rows->isEmpty()) {
            Storage::disk('local')->delete($this->importDir() . '/' . $filename);
            return back()->with('error', 'File is empty or could not be read.');
        }

        $header = $rows->first();
        if (collect($header)->filter(fn($v) => trim((string) $v) !== '')->isEmpty()) {
            Storage::disk('local')->delete($this->importDir() . '/' . $filename);
            return back()->with('error', "Couldn't detect column headers — make sure the first row has column names.");
        }

        $service = new LeadImportService();

        return view('tenant.leads.import-preview', [
            'importToken'   => $filename,
            'headers'       => $header->values(),
            'previewRows'   => $rows->slice(1, 5)->values(),
            'fieldOptions'  => $service->fieldOptions(),
            'customOptions' => $service->customFieldOptions($this->tenantId()),
        ]);
    }

    // ── Step 3: apply mapping, import rows, show summary ────────────
    public function confirm(Request $request): View|RedirectResponse
    {
        $request->validate([
            'import_token' => ['required', 'string'],
            'mapping'      => ['required', 'array'],
        ]);

        $mapping = $request->input('mapping');

        if (!in_array('name', $mapping, true) || !in_array('phone', $mapping, true)) {
            return back()->with('error', 'Name and Phone columns must be mapped before importing.')->withInput();
        }

        $relativePath = $this->importDir() . '/' . basename($request->input('import_token'));

        if (!Storage::disk('local')->exists($relativePath)) {
            return redirect()->route('tenant.leads.import')->with('error', 'Import session expired — please upload the file again.');
        }

        $path   = Storage::disk('local')->path($relativePath);
        $sheets = Excel::toCollection(null, $path);
        $rows   = ($sheets->first() ?? collect())->slice(1)->values()->toArray();

        $result = (new LeadImportService())->process($this->tenantId(), $rows, $mapping);

        Storage::disk('local')->delete($relativePath);

        return view('tenant.leads.import-result', $result + ['type' => 'Leads', 'backRoute' => 'tenant.leads.index']);
    }

    // ── Sample CSV download ──────────────────────────────────────────
    public function template(): Response
    {
        $headers = ['Name', 'Phone', 'Email', 'Company', 'Designation', 'City', 'State', 'Source', 'Status', 'Priority', 'Lead Value', 'Notes', 'Expected Close Date'];
        $csv     = implode(',', $headers) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_import_template.csv"',
        ]);
    }

    // ── Export (respects current index filters) ─────────────────────
    public function export(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->user_type === 'tenant_admin';

        return Excel::download(
            new LeadsExport($this->tenantId(), $request->query(), $isAdmin, $user->id),
            'leads_export_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
