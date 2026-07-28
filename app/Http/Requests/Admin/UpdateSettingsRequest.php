<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-settings');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array'],
        ];

        foreach (array_keys(SettingsController::FIELDS) as $key) {
            $rules["settings.{$key}"] = match ($key) {
                'company_name' => ['required', 'string', 'max:255'],
                'hr_contact_email' => ['required', 'email', 'max:255'],
                'salary_currency' => ['required', 'string', 'size:3', 'alpha'],
                default => ['nullable', 'string', 'max:2000'],
            };
        }

        return $rules;
    }

    /**
     * Only whitelisted keys are ever written — anything else in the payload
     * is silently discarded before validation.
     */
    protected function prepareForValidation(): void
    {
        $settings = collect($this->input('settings', []))
            ->only(array_keys(SettingsController::FIELDS))
            ->all();

        $this->merge(['settings' => $settings]);
    }
}
