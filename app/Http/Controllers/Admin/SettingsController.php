<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * The editable settings and their form labels. Keys not listed here are
     * never writable through this screen.
     *
     * @var array<string, array{label: string, type: string, hint?: string}>
     */
    public const FIELDS = [
        'company_name' => ['label' => 'Company name', 'type' => 'text'],
        'company_address' => ['label' => 'Company address', 'type' => 'textarea'],
        'hr_contact_email' => ['label' => 'HR contact email', 'type' => 'email'],
        'salary_currency' => ['label' => 'Salary currency', 'type' => 'text', 'hint' => 'ISO 4217 code, e.g. USD'],
        'letter_footer_text' => ['label' => 'Letter footer text', 'type' => 'textarea'],
        'company_logo_path' => ['label' => 'Company logo path', 'type' => 'text', 'hint' => 'File upload arrives with the letter module'],
        'signature_image_path' => ['label' => 'Signature image path', 'type' => 'text', 'hint' => 'File upload arrives with the letter module'],
    ];

    public function edit(): View
    {
        $values = collect(array_keys(self::FIELDS))
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key)])
            ->all();

        return view('admin.settings.edit', [
            'fields' => self::FIELDS,
            'values' => $values,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'settings-updated');
    }
}
