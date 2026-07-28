<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterheadUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function baseSettings(): array
    {
        return [
            'company_name' => 'Acme Manufacturing Ltd',
            'company_address' => '1 Industrial Park Way',
            'hr_contact_email' => 'hr@example.com',
            'salary_currency' => 'USD',
            'letter_footer_text' => 'Issued electronically.',
        ];
    }

    public function test_a_super_admin_can_upload_a_logo_and_it_lands_on_the_private_disk(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->baseSettings(),
                'company_logo_path' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $path = Setting::get('company_logo_path');

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('private')->exists($path));

        // Never reachable over HTTP: it is not on the public disk.
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_replacing_an_image_deletes_the_previous_file(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => $this->baseSettings(),
            'signature_image_path' => UploadedFile::fake()->image('sig-one.png'),
        ]);

        $first = Setting::get('signature_image_path');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => $this->baseSettings(),
            'signature_image_path' => UploadedFile::fake()->image('sig-two.png'),
        ]);

        $second = Setting::get('signature_image_path');

        $this->assertNotSame($first, $second);
        $this->assertFalse(Storage::disk('private')->exists($first), 'The old signature should be deleted.');
        $this->assertTrue(Storage::disk('private')->exists($second));
    }

    public function test_the_remove_checkbox_clears_the_image(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => $this->baseSettings(),
            'company_logo_path' => UploadedFile::fake()->image('logo.png'),
        ]);

        $path = Setting::get('company_logo_path');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => $this->baseSettings(),
            'remove_company_logo_path' => '1',
        ]);

        $this->assertNull(Setting::get('company_logo_path'));
        $this->assertFalse(Storage::disk('private')->exists($path));
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->baseSettings(),
                'company_logo_path' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('company_logo_path');

        $this->assertNull(Setting::get('company_logo_path'));
    }

    public function test_an_svg_is_rejected_because_it_can_carry_script(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->baseSettings(),
                'signature_image_path' => UploadedFile::fake()->create('sig.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('signature_image_path');
    }

    public function test_only_a_super_admin_may_change_letterhead(): void
    {
        $hrAdmin = User::factory()->role(Role::HrAdmin)->create();

        $this->actingAs($hrAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->baseSettings(),
                'company_logo_path' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertForbidden();
    }

    public function test_the_settings_screen_renders_with_the_upload_controls(): void
    {
        $admin = User::factory()->role(Role::SuperAdmin)->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Letterhead')
            ->assertSee('Authorised signature');
    }
}
