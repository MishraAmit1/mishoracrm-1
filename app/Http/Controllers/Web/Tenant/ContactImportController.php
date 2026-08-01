<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Exports\ContactsExport;
use App\Http\Controllers\Controller;
use App\Services\Import\ContactImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ContactImportController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function importDir(): string
    {
        return 'imports/' . $this->tenantId();
    }

    public function show(): View
    {
        return view('tenant.contacts.import');
    }

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

        return view('tenant.contacts.import-preview', [
            'importToken'  => $filename,
            'headers'      => $header->values(),
            'previewRows'  => $rows->slice(1, 5)->values(),
            'fieldOptions' => (new ContactImportService())->fieldOptions(),
        ]);
    }

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
            return redirect()->route('tenant.contacts.import')->with('error', 'Import session expired — please upload the file again.');
        }

        $path   = Storage::disk('local')->path($relativePath);
        $sheets = Excel::toCollection(null, $path);
        $rows   = ($sheets->first() ?? collect())->slice(1)->values()->toArray();

        $result = (new ContactImportService())->process($this->tenantId(), $rows, $mapping);

        Storage::disk('local')->delete($relativePath);

        return view('tenant.leads.import-result', $result + ['type' => 'Contacts', 'backRoute' => 'tenant.contacts.index']);
    }

    public function template(): Response
    {
        $headers = ['Name', 'Phone', 'Email', 'Company', 'Designation', 'GST Number', 'Address', 'City', 'State', 'Pincode', 'Notes'];
        $csv     = implode(',', $headers) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts_import_template.csv"',
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new ContactsExport($this->tenantId(), $request->query()),
            'contacts_export_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
