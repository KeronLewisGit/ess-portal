<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Production', 'Quality Assurance', 'Maintenance', 'Logistics',
            'Human Resources', 'Finance', 'Procurement', 'Health & Safety',
        ]);

        return [
            'name' => $name,
            'code' => strtoupper(Str::of($name)->replaceMatches('/[^A-Za-z]/', '')->substr(0, 4)),
            'head_employee_id' => null,
        ];
    }
}
