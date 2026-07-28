<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LetterDownloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An issued letter with a real file on the private disk.
     */
    private function issuedLetterFor(?Employee $employee = null): IssuedLetter
    {
        $employee ??= Employee::factory()->create();

        $request = LetterRequest::factory()->approved()->create(['employee_id' => $employee->id]);

        $body = '%PDF-1.4 fake document body';
        $path = 'letters/test-'.uniqid().'.pdf';
        Storage::disk('private')->put($path, $body);

        return IssuedLetter::factory()->create([
            'letter_request_id' => $request->id,
            'file_path' => $path,
            'file_hash' => hash('sha256', $body),
            'file_size' => strlen($body),
        ]);
    }

    private function signedUrlFor(IssuedLetter $letter): string
    {
        return URL::signedRoute('letters.download', ['issuedLetter' => $letter->id], now()->addMinutes(15));
    }

    public function test_the_owning_employee_can_download_their_letter(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $letter = $this->issuedLetterFor($employee);

        $this->actingAs($user)
            ->get($this->signedUrlFor($letter))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_another_employee_cannot_download_someone_elses_letter(): void
    {
        $intruderEmployee = Employee::factory()->create();
        $intruder = User::factory()->role(Role::Employee)->create(['employee_id' => $intruderEmployee->id]);
        $letter = $this->issuedLetterFor();

        // Even holding a perfectly valid signed URL, the policy still refuses.
        $this->actingAs($intruder)
            ->get($this->signedUrlFor($letter))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_even_with_a_valid_signature(): void
    {
        $letter = $this->issuedLetterFor();

        $this->get($this->signedUrlFor($letter))->assertRedirect(route('login'));
    }

    public function test_an_unsigned_or_tampered_url_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $letter = $this->issuedLetterFor($employee);

        // No signature at all.
        $this->actingAs($user)
            ->get(route('letters.download', $letter))
            ->assertForbidden();

        // Signature present but the query string was altered.
        $this->actingAs($user)
            ->get($this->signedUrlFor($letter).'&extra=1')
            ->assertForbidden();
    }

    public function test_an_expired_signed_url_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $letter = $this->issuedLetterFor($employee);

        $url = URL::signedRoute('letters.download', ['issuedLetter' => $letter->id], now()->addMinutes(15));

        $this->travel(16)->minutes();

        $this->actingAs($user)->get($url)->assertForbidden();
    }

    public function test_the_prepare_route_mints_a_signed_url(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $letter = $this->issuedLetterFor($employee);

        $response = $this->actingAs($user)->get(route('letters.prepare', $letter));

        $response->assertRedirect();
        $this->assertStringContainsString('signature=', $response->headers->get('Location'));
    }

    public function test_a_tampered_file_on_disk_is_refused_rather_than_served(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $letter = $this->issuedLetterFor($employee);

        // Someone swaps the PDF underneath us; the stored hash no longer matches.
        Storage::disk('private')->put($letter->file_path, '%PDF-1.4 substituted content');

        $this->actingAs($user)
            ->get($this->signedUrlFor($letter))
            ->assertStatus(409);
    }

    public function test_an_employee_cannot_download_a_revoked_letter_but_hr_can(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $hr = User::factory()->role(Role::HrOfficer)->create();

        $letter = $this->issuedLetterFor($employee);
        $letter->forceFill(['revoked_at' => now(), 'revoked_reason' => 'Issued in error.'])->save();

        $this->actingAs($user)->get($this->signedUrlFor($letter))->assertForbidden();
        $this->actingAs($hr)->get($this->signedUrlFor($letter))->assertOk();
    }
}
