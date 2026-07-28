<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $hired = fake()->dateTimeBetween('-8 years', '-1 month');

        return [
            'employee_code' => 'EMP'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional(0.3)->firstName(),
            'national_id' => fake()->numerify('ID########'),
            'work_email' => fake()->unique()->safeEmail(),
            'personal_email' => fake()->optional(0.5)->safeEmail(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'manager_id' => null,
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'employment_status' => EmploymentStatus::Active,
            'date_hired' => $hired,
            'date_separated' => null,
            'annual_salary' => fake()->numberBetween(24000, 180000),
            'salary_currency' => config('ess.defaults.salary_currency', 'USD'),
            'pay_frequency' => PayFrequency::Monthly,
        ];
    }

    public function separated(): static
    {
        return $this->state(fn () => [
            'employment_status' => EmploymentStatus::Separated,
            'date_separated' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
