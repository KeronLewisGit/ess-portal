<?php

namespace App\Http\Requests\Employee;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvisionUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('provisionUser', $this->route('employee')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Restricted to the roles the acting user may actually grant, so
            // an hr_admin cannot mint a super_admin account (escalation).
            'role' => ['required', Rule::enum(Role::class)->only($this->assignableRoles())],
        ];
    }

    public function messages(): array
    {
        return [
            'role.enum' => 'You are not allowed to assign that role.',
        ];
    }

    /**
     * @return array<int, Role>
     */
    private function assignableRoles(): array
    {
        return $this->user()?->role?->assignableRoles() ?? [];
    }
}
