<?php

namespace App\Http\Requests\LetterType;

use App\Models\LetterType;
use Illuminate\Foundation\Http\FormRequest;

class StoreLetterTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LetterType::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:letter_types,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'body_template' => ['required', 'string', 'max:20000'],
            // Feeds the reference number, so keep it short and predictable.
            'reference_prefix' => ['required', 'string', 'max:10', 'alpha_num'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => strtoupper((string) $this->input('code')),
            'reference_prefix' => strtoupper((string) $this->input('reference_prefix')),
        ]);
    }
}
