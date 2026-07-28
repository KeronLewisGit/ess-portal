<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Idempotent: employees keyed on employee_code. Also links the Phase 1
     * demo "employee@example.com" user to a concrete employee record so the
     * employee-facing policies have something to resolve against.
     */
    public function run(): void
    {
        $production = Department::query()->where('code', 'PROD')->first();
        $hr = Department::query()->where('code', 'HR')->first();

        // A named demo employee linked to the demo employee user account.
        $demo = Employee::query()->firstOrCreate(
            ['employee_code' => 'EMP0001'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Employee',
                'national_id' => 'ID1000001',
                'work_email' => 'employee@example.com',
                'phone' => '+1-555-0100',
                'job_title' => 'Machine Operator',
                'department_id' => $production?->id,
                'employment_type' => EmploymentType::Permanent,
                'employment_status' => EmploymentStatus::Active,
                'date_hired' => now()->subYears(3)->startOfDay(),
                'annual_salary' => 48000,
                'salary_currency' => config('ess.defaults.salary_currency', 'USD'),
                'pay_frequency' => PayFrequency::Monthly,
            ],
        );

        User::query()->where('email', 'employee@example.com')
            ->whereNull('employee_id')
            ->update(['employee_id' => $demo->id]);

        // An HR-linked demo employee for the hr.officer account.
        Employee::query()->firstOrCreate(
            ['employee_code' => 'EMP0002'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Officer',
                'national_id' => 'ID1000002',
                'work_email' => 'hr.officer@example.com',
                'job_title' => 'HR Officer',
                'department_id' => $hr?->id,
                'employment_type' => EmploymentType::Permanent,
                'employment_status' => EmploymentStatus::Active,
                'date_hired' => now()->subYears(5)->startOfDay(),
                'annual_salary' => 62000,
                'salary_currency' => config('ess.defaults.salary_currency', 'USD'),
                'pay_frequency' => PayFrequency::Monthly,
            ],
        );

        // A spread of additional employees for list/search/pagination, only
        // seeded once (when the table has just the two demo rows).
        if (Employee::query()->count() < 3) {
            Employee::factory()
                ->count(24)
                ->state(fn () => [
                    'department_id' => Department::query()->inRandomOrder()->value('id'),
                ])
                ->create();
        }
    }
}
