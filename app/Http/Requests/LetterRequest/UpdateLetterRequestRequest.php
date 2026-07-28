<?php

namespace App\Http\Requests\LetterRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The policy also enforces that the request is still a draft.
        return $this->user()?->can('update', $this->route('letter_request')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
        $this->merge([
            'include_salary' => $this->boolean('include_salary'),
        ]);
    }
}
