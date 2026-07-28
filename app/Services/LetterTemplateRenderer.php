<?php

namespace App\Services;

use App\Models\LetterRequest;
use App\Models\Setting;

/**
 * Substitutes {{ placeholder }} tokens in a letter type's body_template.
 *
 * This is the ONLY place the encrypted salary is read for a letter, and it is
 * read solely when the employee opted in AND the request was approved — the
 * caller passes the request, never a raw flag.
 */
class LetterTemplateRenderer
{
    /**
     * Build the substitution map for a request. Returned separately from
     * render() so the same values can be snapshotted onto the issued letter.
     *
     * @return array<string, string>
     */
    public function values(LetterRequest $request): array
    {
        $employee = $request->employee;

        return [
            'employee_name' => (string) $employee->full_name,
            'employee_code' => (string) $employee->employee_code,
            'job_title' => (string) ($employee->job_title ?? '—'),
            'department' => (string) ($employee->department?->name ?? '—'),
            'date_hired' => $employee->date_hired?->format('d F Y') ?? '—',
            'employment_type' => $employee->employment_type?->label() ?? '—',
            'employment_status' => $employee->employment_status?->label() ?? '—',
            'company_name' => (string) Setting::get('company_name', (string) config('app.name')),
            'company_address' => (string) Setting::get('company_address', ''),
            'addressed_to' => $request->addressed_to ?: 'To whom it may concern',
            'reference_number' => (string) $request->reference_number,
            'issue_date' => now()->format('d F Y'),
            'salary' => $this->salary($request),
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        $replacements = [];

        foreach ($values as $token => $value) {
            // Tolerate both {{token}} and {{ token }} in HR-authored templates.
            $replacements['{{'.$token.'}}'] = $value;
            $replacements['{{ '.$token.' }}'] = $value;
        }

        return strtr($template, $replacements);
    }

    /**
     * The salary figure, formatted, or a neutral placeholder when the
     * employee did not opt in. Never returns the raw value for a request
     * that did not ask for it.
     */
    private function salary(LetterRequest $request): string
    {
        if (! $request->include_salary) {
            return '—';
        }

        $employee = $request->employee;
        $amount = $employee->annual_salary;

        if ($amount === null || $amount === '') {
            return '—';
        }

        $currency = $employee->salary_currency
            ?: Setting::get('salary_currency', (string) config('ess.defaults.salary_currency', 'USD'));

        return $currency.' '.number_format((float) $amount, 2).' per annum';
    }

    /**
     * The subset of values safe to persist on the issued letter row.
     * Salary is deliberately excluded — it exists only inside the PDF.
     *
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    public function snapshot(array $values): array
    {
        return collect($values)->except(['salary'])->all();
    }
}
