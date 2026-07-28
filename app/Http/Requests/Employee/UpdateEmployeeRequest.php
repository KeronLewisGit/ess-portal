<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            'employee_code' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            // Leave blank to keep the stored (encrypted) value unchanged.
            'national_id' => ['nullable', 'string', 'max:255'],
            'work_email' => ['required', 'email', 'max:255', Rule::unique('employees', 'work_email')->ignore($employeeId)],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id', Rule::notIn([$employeeId])],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'date_hired' => ['nullable', 'date'],
            'date_separated' => ['nullable', 'date', 'after_or_equal:date_hired'],
            // Leave blank to keep the stored (encrypted) value unchanged.
            'annual_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'pay_frequency' => ['required', Rule::enum(PayFrequency::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'manager_id.not_in' => 'An employee cannot be their own manager.',
        ];
    }
}
