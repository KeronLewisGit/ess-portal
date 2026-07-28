<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Parses an uploaded CSV/XLSX of employees and either validates it (dry run,
 * writes nothing) or imports it. Every row is validated independently so a
 * single bad row is reported rather than aborting the whole file.
 */
class EmployeeImportService
{
    /**
     * The expected heading-row columns. Documented in the UI and the sample
     * template (EmployeeImportController::template()).
     *
     * @var array<int, string>
     */
    public const COLUMNS = [
        'employee_code', 'first_name', 'last_name', 'middle_name', 'national_id',
        'work_email', 'personal_email', 'phone', 'job_title', 'department_code',
        'manager_employee_code', 'employment_type', 'employment_status',
        'date_hired', 'annual_salary', 'salary_currency', 'pay_frequency',
    ];

    /**
     * Validate a file without writing anything.
     *
     * @return array{rows: int, valid: int, errors: array<int, array{row: int, messages: array<int, string>}>, prepared: array<int, array<string, mixed>>}
     */
    public function dryRun(string $path): array
    {
        $rows = $this->read($path);

        $errors = [];
        $prepared = [];
        $preparedRowNumbers = [];
        $seenCodes = [];
        $seenEmails = [];

        foreach ($rows as $index => $row) {
            // +2: skip the heading row and switch to 1-based numbering.
            $rowNumber = $index + 2;

            [$data, $messages] = $this->validateRow($row, $seenCodes, $seenEmails);

            if ($messages !== []) {
                $errors[] = ['row' => $rowNumber, 'messages' => $messages];

                continue;
            }

            $seenCodes[] = $data['employee_code'];
            $seenEmails[] = $data['work_email'];
            $prepared[] = $data;
            $preparedRowNumbers[] = $rowNumber;
        }

        // Second pass for managers. A manager may be defined further down the
        // same file, so the code can't be checked row-by-row with `exists:` —
        // it is resolved against existing employees PLUS the codes in this
        // file. An unresolvable code is a hard error: silently importing the
        // employee with no manager would hide a typo.
        $managerErrors = $this->managerErrors($prepared, $preparedRowNumbers, $seenCodes);

        foreach ($managerErrors as $error) {
            $errors[] = $error;
        }

        if ($errors !== []) {
            // Keep the report ordered by row number after merging both passes.
            usort($errors, fn (array $a, array $b) => $a['row'] <=> $b['row']);
        }

        return [
            'rows' => $rows->count(),
            'valid' => count($prepared) - count($managerErrors),
            'errors' => $errors,
            'prepared' => $prepared,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $prepared
     * @param  array<int, int>  $rowNumbers
     * @param  array<int, string>  $fileCodes
     * @return array<int, array{row: int, messages: array<int, string>}>
     */
    private function managerErrors(array $prepared, array $rowNumbers, array $fileCodes): array
    {
        $wanted = array_values(array_unique(array_filter(
            array_column($prepared, 'manager_employee_code')
        )));

        if ($wanted === []) {
            return [];
        }

        $known = array_merge(
            $fileCodes,
            Employee::whereIn('employee_code', $wanted)->pluck('employee_code')->all(),
        );

        $errors = [];

        foreach ($prepared as $i => $data) {
            $code = $data['manager_employee_code'] ?? null;

            if ($code !== null && ! in_array($code, $known, true)) {
                $errors[] = [
                    'row' => $rowNumbers[$i],
                    'messages' => ["Unknown manager_employee_code \"{$code}\"."],
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate then import. If any row is invalid, nothing is written and the
     * error report is returned (import is all-or-nothing on a clean file).
     *
     * @return array{imported: int, rows: int, valid: int, errors: array<int, array{row: int, messages: array<int, string>}>}
     */
    public function import(string $path): array
    {
        $result = $this->dryRun($path);

        if ($result['errors'] !== []) {
            return [
                'imported' => 0,
                'rows' => $result['rows'],
                'valid' => $result['valid'],
                'errors' => $result['errors'],
            ];
        }

        $imported = DB::transaction(function () use ($result) {
            $pendingManagers = [];

            // Pass 1: create every employee. Manager links are deferred so a
            // manager listed further down the file still resolves.
            foreach ($result['prepared'] as $data) {
                $manager = $data['manager_employee_code'] ?? null;
                unset($data['manager_employee_code']);

                $employee = Employee::create($data);

                if ($manager !== null) {
                    $pendingManagers[$employee->id] = $manager;
                }
            }

            // Pass 2: link managers now that every code in the file exists.
            // Dry-run already proved each code resolves, so a miss here would
            // be a bug, not user input.
            if ($pendingManagers !== []) {
                $ids = Employee::whereIn('employee_code', array_values($pendingManagers))
                    ->pluck('id', 'employee_code');

                foreach ($pendingManagers as $employeeId => $code) {
                    if ($ids->has($code)) {
                        Employee::find($employeeId)?->update(['manager_id' => $ids->get($code)]);
                    }
                }
            }

            return count($result['prepared']);
        });

        return [
            'imported' => $imported,
            'rows' => $result['rows'],
            'valid' => $result['valid'],
            'errors' => [],
        ];
    }

    /**
     * @return Collection<int, Collection<string, mixed>>
     */
    private function read(string $path)
    {
        // WithHeadingRow keys each row by the header and skips the header row
        // itself, so rows are addressable by column name.
        $import = new class implements ToCollection, WithHeadingRow
        {
            public $rows;

            public function collection($rows): void
            {
                $this->rows = $rows;
            }
        };

        Excel::import($import, $path);

        return $import->rows ?? collect();
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function validateRow($row, array $seenCodes, array $seenEmails): array
    {
        $raw = collect(self::COLUMNS)
            ->mapWithKeys(fn (string $col) => [$col => $this->clean($row[$col] ?? null)])
            ->all();

        $validator = Validator::make($raw, [
            'employee_code' => ['required', 'string', 'max:255', 'unique:employees,employee_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'work_email' => ['required', 'email', 'max:255', 'unique:employees,work_email'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_code' => ['nullable', 'string', 'exists:departments,code'],
            'manager_employee_code' => ['nullable', 'string'],
            'employment_type' => ['required', 'string', 'in:'.implode(',', EmploymentType::values())],
            'employment_status' => ['required', 'string', 'in:'.implode(',', EmploymentStatus::values())],
            'date_hired' => ['nullable', 'date'],
            'annual_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'pay_frequency' => ['required', 'string', 'in:'.implode(',', PayFrequency::values())],
        ]);

        $messages = $validator->errors()->all();

        // In-file duplicate detection (across earlier rows in the same file).
        if (in_array($raw['employee_code'], $seenCodes, true)) {
            $messages[] = 'Duplicate employee_code within the file.';
        }
        if (in_array($raw['work_email'], $seenEmails, true)) {
            $messages[] = 'Duplicate work_email within the file.';
        }

        if ($messages !== []) {
            return [[], $messages];
        }

        $departmentId = $raw['department_code'] !== null
            ? Department::where('code', $raw['department_code'])->value('id')
            : null;

        $data = [
            'employee_code' => $raw['employee_code'],
            'first_name' => $raw['first_name'],
            'last_name' => $raw['last_name'],
            'middle_name' => $raw['middle_name'],
            'national_id' => $raw['national_id'],
            'work_email' => $raw['work_email'],
            'personal_email' => $raw['personal_email'],
            'phone' => $raw['phone'],
            'job_title' => $raw['job_title'],
            'department_id' => $departmentId,
            'employment_type' => $raw['employment_type'],
            'employment_status' => $raw['employment_status'],
            'date_hired' => $raw['date_hired'],
            'annual_salary' => $raw['annual_salary'],
            'salary_currency' => $raw['salary_currency'] ?: config('ess.defaults.salary_currency', 'USD'),
            'pay_frequency' => $raw['pay_frequency'],
            'manager_employee_code' => $raw['manager_employee_code'],
        ];

        return [$data, []];
    }

    private function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
