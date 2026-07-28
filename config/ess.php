<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ESS Portal application configuration
    |--------------------------------------------------------------------------
    |
    | Environment-driven values specific to the ESS Portal. Company-facing
    | values (name, address, HR contact, currency) are seeded into the
    | `settings` table from these env values and are editable in the UI by
    | super_admin thereafter.
    |
    */

    'defaults' => [
        'company_name' => env('COMPANY_NAME', 'Acme Manufacturing Ltd'),
        'company_address' => env('COMPANY_ADDRESS', ''),
        'hr_contact_email' => env('HR_CONTACT_EMAIL', 'hr@example.com'),
        'salary_currency' => env('SALARY_CURRENCY', 'USD'),
    ],

    // Lifetime of signed document download URLs (minutes).
    'signed_url_expiry_minutes' => (int) env('SIGNED_URL_EXPIRY_MINUTES', 15),

    // Rate limits (enforced from Phase 3/5 onwards; configured now).
    'rate_limits' => [
        'payslips_per_hour' => (int) env('RATE_LIMIT_PAYSLIPS_PER_HOUR', 20),
        'letter_requests_per_day' => (int) env('RATE_LIMIT_LETTER_REQUESTS_PER_DAY', 10),
        'login_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 5),
    ],

    // Optional TOTP 2FA for hr_admin / super_admin (implemented in a later phase).
    'two_factor_enabled' => (bool) env('TWO_FACTOR_ENABLED', false),

];
