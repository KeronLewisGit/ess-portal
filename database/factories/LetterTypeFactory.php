<?php

namespace Database\Factories;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LetterType>
 */
class LetterTypeFactory extends Factory
{
    protected $model = LetterType::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Employment Confirmation', 'Bank Loan Letter', 'Visa Support Letter',
            'Salary Certificate', 'Experience Letter', 'Embassy Letter',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'code' => strtoupper(Str::slug($name, '_')),
            'description' => fake()->sentence(),
            'body_template' => 'This is to certify that {{employee_name}} ({{employee_code}}) '
                ."is employed as {{job_title}} in {{department}} since {{date_hired}}.\n\n"
                .'Issued on {{issue_date}} under reference {{reference_number}}.',
            'reference_prefix' => strtoupper(fake()->unique()->lexify('??')),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
