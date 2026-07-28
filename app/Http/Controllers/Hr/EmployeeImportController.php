<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ImportEmployeesRequest;
use App\Models\Employee;
use App\Services\EmployeeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeImportController extends Controller
{
    // Uploaded files awaiting confirmation live here on the PRIVATE disk.
    private const STAGING_DIR = 'imports/employees';

    public function __construct(private readonly EmployeeImportService $service) {}

    public function create(): View
    {
        $this->authorize('manage', Employee::class);

        return view('hr.employees.import', [
            'columns' => EmployeeImportService::COLUMNS,
            'result' => null,
            'token' => null,
        ]);
    }

    /**
     * Dry-run: validate the uploaded file and show a row-by-row report. The
     * file is staged privately so it can be committed on confirmation. Nothing
     * is written to the employees table here.
     */
    public function preview(ImportEmployeesRequest $request): View
    {
        $token = (string) Str::uuid();
        $extension = $request->file('file')->getClientOriginalExtension() ?: 'csv';
        $path = self::STAGING_DIR."/{$token}.{$extension}";

        Storage::disk('private')->put($path, $request->file('file')->get());

        $result = $this->service->dryRun(Storage::disk('private')->path($path));

        return view('hr.employees.import', [
            'columns' => EmployeeImportService::COLUMNS,
            'result' => $result,
            'token' => $result['errors'] === [] ? $token : null,
            'stagedExtension' => $extension,
        ]);
    }

    /**
     * Commit a previously previewed, error-free file.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Employee::class);

        // The token is interpolated into a storage path, so it is constrained
        // to the UUID shape it was minted as — no traversal, no wildcards.
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'extension' => ['required', 'string', 'in:csv,txt,xls,xlsx'],
        ]);

        $path = self::STAGING_DIR."/{$validated['token']}.{$validated['extension']}";

        abort_unless(Storage::disk('private')->exists($path), 404);

        $result = $this->service->import(Storage::disk('private')->path($path));

        Storage::disk('private')->delete($path);

        if ($result['errors'] !== []) {
            return redirect()
                ->route('hr.employees.import.create')
                ->with('status', 'Import aborted: the file no longer validates cleanly.');
        }

        return redirect()
            ->route('hr.employees.index')
            ->with('status', "Imported {$result['imported']} employee(s).");
    }

    /**
     * Downloadable sample template documenting the expected columns.
     */
    public function template(): Response
    {
        $this->authorize('manage', Employee::class);

        $header = implode(',', EmployeeImportService::COLUMNS);
        $sample = implode(',', [
            'EMP1001', 'Jane', 'Doe', '', 'ID12345678',
            'jane.doe@example.com', '', '+1-555-0101', 'Line Supervisor', 'PROD',
            '', 'permanent', 'active',
            '2024-01-15', '52000', 'USD', 'monthly',
        ]);

        return response($header."\n".$sample."\n", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee-import-template.csv"',
        ]);
    }
}
