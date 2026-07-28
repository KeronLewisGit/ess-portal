<?php

namespace Database\Factories;

use App\Enums\LetterRequestStatus;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LetterRequest>
 */
class LetterRequestFactory extends Factory
{
    protected $model = LetterRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'letter_type_id' => LetterType::factory(),
            'status' => LetterRequestStatus::Draft,
            'include_salary' => false,
            'addressed_to' => fake()->company(),
            'purpose' => fake()->sentence(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => LetterRequestStatus::Submitted,
            'submitted_at' => now(),
            'reference_number' => 'TEST-'.fake()->unique()->numberBetween(10000, 99999),
        ]);
    }

    public function withSalary(): static
    {
        return $this->state(fn () => ['include_salary' => true]);
    }

    public function approved(): static
    {
        return $this->submitted()->state(fn () => [
            'status' => LetterRequestStatus::Approved,
            'decided_at' => now(),
        ]);
    }
}
