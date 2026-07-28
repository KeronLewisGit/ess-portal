<?php

namespace App\Http\Requests\LetterRequest;

use App\Models\LetterRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LetterRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Only an active template may be requested.
            'letter_type_id' => [
                'required',
                Rule::exists('letter_types', 'id')->where('is_active', true),
            ],
            'addressed_to' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'include_salary' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // An unchecked checkbox is absent from the payload entirely.
        $this->merge([
            'include_salary' => $this->boolean('include_salary'),
        ]);
    }

    public function messages(): array
    {
        return [
            'letter_type_id.exists' => 'That letter type is not available.',
            'purpose.required' => 'Please say what the letter is needed for.',
        ];
    }
}
