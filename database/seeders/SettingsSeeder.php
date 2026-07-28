<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Idempotent: only fills settings that do not exist yet, so values
     * edited in the UI are never overwritten by re-seeding.
     */
    public function run(): void
    {
        $defaults = [
            'company_name' => config('ess.defaults.company_name'),
            'company_address' => config('ess.defaults.company_address'),
            'hr_contact_email' => config('ess.defaults.hr_contact_email'),
            'salary_currency' => config('ess.defaults.salary_currency'),
            'letter_footer_text' => 'This letter was issued electronically by '
                .config('ess.defaults.company_name')
                .'. Its authenticity can be verified using the reference number above.',
            'company_logo_path' => null,
            'signature_image_path' => null,
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
