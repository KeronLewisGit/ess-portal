<?php

namespace Database\Seeders;

use App\Models\LetterType;
use Illuminate\Database\Seeder;

class LetterTypeSeeder extends Seeder
{
    /**
     * Idempotent: keyed on the unique code. The wording is a starting point —
     * HR edits these in the UI, which is why they live in the database.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Employment Confirmation',
                'code' => 'EMPLOYMENT_CONFIRMATION',
                'reference_prefix' => 'EC',
                'description' => 'Confirms that the employee works here, without stating pay.',
                'body_template' => <<<'TXT'
                    To {{addressed_to}},

                    This is to confirm that {{employee_name}} ({{employee_code}}) is employed
                    by {{company_name}} as {{job_title}} in the {{department}} department,
                    and has been since {{date_hired}}. Their current employment status is
                    {{employment_status}} on a {{employment_type}} basis.

                    This letter is issued on {{issue_date}} under reference
                    {{reference_number}} at the employee's request.
                    TXT,
            ],
            [
                'name' => 'Salary Certificate',
                'code' => 'SALARY_CERTIFICATE',
                'reference_prefix' => 'SC',
                'description' => 'States current salary — for banks and lenders.',
                'body_template' => <<<'TXT'
                    To {{addressed_to}},

                    This is to certify that {{employee_name}} ({{employee_code}}) is employed
                    by {{company_name}} as {{job_title}} since {{date_hired}}.

                    Their current annual salary is {{salary}}.

                    This letter is issued on {{issue_date}} under reference
                    {{reference_number}} at the employee's request.
                    TXT,
            ],
            [
                'name' => 'Bank Loan Letter',
                'code' => 'BANK_LOAN',
                'reference_prefix' => 'BL',
                'description' => 'Employment and salary confirmation addressed to a lender.',
                'body_template' => <<<'TXT'
                    To {{addressed_to}},

                    {{employee_name}} ({{employee_code}}) has been employed by
                    {{company_name}} as {{job_title}} since {{date_hired}} on a
                    {{employment_type}} basis. Their current annual salary is {{salary}}.

                    This letter is issued on {{issue_date}} under reference
                    {{reference_number}} in support of a loan application.
                    TXT,
            ],
            [
                'name' => 'Visa Support Letter',
                'code' => 'VISA_SUPPORT',
                'reference_prefix' => 'VS',
                'description' => 'Supports a travel or visa application.',
                'body_template' => <<<'TXT'
                    To {{addressed_to}},

                    This letter confirms that {{employee_name}} ({{employee_code}}) is
                    employed by {{company_name}} as {{job_title}} since {{date_hired}}
                    and remains in our employment.

                    {{company_name}} supports their application. The employee is expected
                    to resume their duties on return.

                    Issued on {{issue_date}} under reference {{reference_number}}.
                    TXT,
            ],
            [
                'name' => 'Experience Letter',
                'code' => 'EXPERIENCE',
                'reference_prefix' => 'EX',
                'description' => 'Service record for a departing or former employee.',
                'body_template' => <<<'TXT'
                    To whom it may concern,

                    This is to certify that {{employee_name}} ({{employee_code}}) was
                    employed by {{company_name}} as {{job_title}} in the {{department}}
                    department from {{date_hired}}.

                    We confirm their service was satisfactory and wish them well.

                    Issued on {{issue_date}} under reference {{reference_number}}.
                    TXT,
            ],
        ];

        foreach ($types as $type) {
            LetterType::query()->firstOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true],
            );
        }
    }
}
