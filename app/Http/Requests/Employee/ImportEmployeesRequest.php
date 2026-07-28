<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Employee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // MIME is sniffed by the validator, not trusted from the extension.
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx,xls'],
        ];
    }
}
