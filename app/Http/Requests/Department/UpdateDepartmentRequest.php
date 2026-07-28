<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('department')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $departmentId = $this->route('department')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('departments', 'code')->ignore($departmentId)],
            'head_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
