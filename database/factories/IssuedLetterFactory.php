<?php

namespace Database\Factories;

use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IssuedLetter>
 */
class IssuedLetterFactory extends Factory
{
    protected $model = IssuedLetter::class;

    public function definition(): array
    {
        $body = 'PDF-'.fake()->uuid();

        return [
            'letter_request_id' => LetterRequest::factory()->approved(),
            'reference_number' => 'TEST-'.fake()->unique()->numberBetween(10000, 99999),
            'verification_token' => Str::random(48),
            'file_path' => 'letters/'.Str::random(12).'.pdf',
            'file_hash' => hash('sha256', $body),
            'file_size' => strlen($body),
            'snapshot' => [
                'employee_name' => fake()->name(),
                'employee_code' => 'EMP'.fake()->unique()->numberBetween(1000, 9999),
                'reference_number' => 'TEST',
            ],
            'issued_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
            'revoked_reason' => 'Issued in error.',
        ]);
    }
}
