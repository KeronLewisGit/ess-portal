<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Employee master mutations. Controllers stay thin and pass already-validated
 * data here. Sensitive fields (national_id, annual_salary) are assigned only
 * through this layer, never bound straight from a request in a controller.
 */
class EmployeeService
{
    public function create(array $data): Employee
    {
        $data['salary_currency'] ??= (string) config('ess.defaults.salary_currency', 'USD');

        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        // Blank salary/national_id fields in an edit form must not wipe stored
        // values — drop them from the payload when not provided.
        foreach (['national_id', 'annual_salary'] as $sensitive) {
            if (array_key_exists($sensitive, $data) && ($data[$sensitive] === null || $data[$sensitive] === '')) {
                unset($data[$sensitive]);
            }
        }

        $employee->update($data);

        return $employee;
    }

    /**
     * Bulk deactivate: mark employees separated. Returns the number affected.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkDeactivate(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        return DB::transaction(function () use ($ids) {
            $affected = 0;

            // Iterate so the audit observer fires per row (mass update would
            // bypass model events and the audit trail).
            Employee::whereIn('id', $ids)
                ->where('employment_status', '!=', EmploymentStatus::Separated->value)
                ->get()
                ->each(function (Employee $employee) use (&$affected) {
                    $employee->update([
                        'employment_status' => EmploymentStatus::Separated,
                        'date_separated' => $employee->date_separated ?? now()->toDateString(),
                    ]);
                    $affected++;
                });

            return $affected;
        });
    }
}
