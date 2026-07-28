<?php

namespace Tests\Feature\Hr;

use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        $header = implode(',', EmployeeImportService::COLUMNS);
        file_put_contents($path, $header."\n".$body);

        return $path;
    }

    public function test_dry_run_reports_errors_without_writing(): void
    {
        Department::factory()->create(['code' => 'PROD']);

        // Row 1: valid. Row 2: missing required work_email + bad enum.
        $csv = $this->writeCsv(
            "EMP100,Jane,Doe,,ID1,jane@example.com,,,,PROD,,permanent,active,2024-01-01,50000,USD,monthly\n".
            "EMP101,John,,,ID2,,,,,PROD,,not_a_type,active,,,,monthly\n"
        );

        $service = app(EmployeeImportService::class);
        $result = $service->dryRun($csv);

        $this->assertSame(2, $result['rows']);
        $this->assertSame(1, $result['valid']);
        $this->assertNotEmpty($result['errors']);
        // The bad row is reported at spreadsheet row 3 (header + 2).
        $this->assertSame(3, $result['errors'][0]['row']);

        // Nothing was written during a dry run.
        $this->assertDatabaseCount('employees', 0);

        @unlink($csv);
    }

    public function test_clean_file_imports_all_rows(): void
    {
        Department::factory()->create(['code' => 'PROD']);

        $csv = $this->writeCsv(
            "EMP200,Alice,Smith,,ID10,alice@example.com,,,,PROD,,permanent,active,2024-01-01,60000,USD,monthly\n".
            "EMP201,Bob,Jones,,ID11,bob@example.com,,,,PROD,,contract,active,2024-02-01,40000,USD,monthly\n"
        );

        $result = app(EmployeeImportService::class)->import($csv);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseCount('employees', 2);
        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP200', 'work_email' => 'alice@example.com']);

        @unlink($csv);
    }

    public function test_a_manager_defined_later_in_the_same_file_is_linked(): void
    {
        // The report is listed before their manager — a forward reference.
        $csv = $this->writeCsv(
            "EMP401,Ivy,Report,,ID31,ivy@example.com,,,,,EMP400,permanent,active,,,,monthly\n".
            "EMP400,Hank,Boss,,ID30,hank@example.com,,,,,,permanent,active,,,,monthly\n"
        );

        $result = app(EmployeeImportService::class)->import($csv);

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['imported']);

        $manager = Employee::where('employee_code', 'EMP400')->first();
        $this->assertSame(
            $manager->id,
            Employee::where('employee_code', 'EMP401')->value('manager_id')
        );

        @unlink($csv);
    }

    public function test_an_unknown_manager_code_is_an_error_not_a_silent_null(): void
    {
        $csv = $this->writeCsv(
            "EMP500,Ken,Typo,,ID40,ken@example.com,,,,,NOPE999,permanent,active,,,,monthly\n"
        );

        $result = app(EmployeeImportService::class)->import($csv);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(0, $result['valid']);
        $this->assertStringContainsString('NOPE999', $result['errors'][0]['messages'][0]);
        $this->assertDatabaseCount('employees', 0);

        @unlink($csv);
    }

    public function test_import_is_aborted_when_any_row_is_invalid(): void
    {
        $csv = $this->writeCsv(
            "EMP300,Carol,White,,ID20,carol@example.com,,,,,,permanent,active,,,,monthly\n".
            "EMP301,Dave,,,ID21,dave@example.com,,,,,,permanent,active,,,,monthly\n" // missing last_name
        );

        $result = app(EmployeeImportService::class)->import($csv);

        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseCount('employees', 0);

        @unlink($csv);
    }
}
