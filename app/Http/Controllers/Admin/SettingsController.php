<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    ];

    /**
     * Letterhead images, uploaded rather than typed. They are stored on the
     * PRIVATE disk (a signature image is sensitive) and embedded into letter
     * PDFs as base64 data URIs, never served over HTTP.
     *
     * @var array<string, array{label: string, hint: string}>
     */
    public const UPLOADS = [
        'company_logo_path' => [
            'label' => 'Company logo',
            'hint' => 'PNG or JPEG, appears top-left on issued letters. Max 2 MB.',
        ],
        'signature_image_path' => [
            'label' => 'Authorised signature',
            'hint' => 'PNG or JPEG of the signature applied to issued letters. Max 2 MB.',
        ],
    ];

    private const UPLOAD_DIR = 'letterhead';

    public function edit(): View
    {
        $values = collect(array_keys(self::FIELDS))
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key)])
            ->all();

        $uploads = collect(array_keys(self::UPLOADS))
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key)])
            ->all();

        return view('admin.settings.edit', [
            'fields' => self::FIELDS,
            'values' => $values,
            'uploads' => self::UPLOADS,
            'uploadValues' => $uploads,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::set($key, $value);
        }

        $this->storeUploads($request);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'settings-updated');
    }

    /**
     * Persist any uploaded letterhead images and point the setting at the new
     * file. The previous file is deleted so old signatures don't linger on
     * disk. A "remove" checkbox clears the setting entirely.
     */
    private function storeUploads(UpdateSettingsRequest $request): void
    {
        $disk = Storage::disk('private');

        foreach (array_keys(self::UPLOADS) as $key) {
            $existing = Setting::get($key);

            if ($request->boolean("remove_{$key}")) {
                if ($existing !== null && $existing !== '') {
                    $disk->delete($existing);
                }

                Setting::set($key, null);

                continue;
            }

            if (! $request->hasFile($key)) {
                continue;
            }

            $file = $request->file($key);
            $path = self::UPLOAD_DIR.'/'.$key.'-'.Str::random(12).'.'.$file->extension();

            $disk->put($path, $file->get());

            if ($existing !== null && $existing !== '' && $existing !== $path) {
                $disk->delete($existing);
            }

            Setting::set($key, $path);
        }
    }
}
