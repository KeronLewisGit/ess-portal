<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function issuedLetter(array $snapshot = []): IssuedLetter
    {
        $type = LetterType::factory()->create(['name' => 'Employment Confirmation']);
        $employee = Employee::factory()->create([
            'first_name' => 'Demo',
            'last_name' => 'Employee',
            'job_title' => 'Machine Operator',
        ]);

        $request = LetterRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'letter_type_id' => $type->id,
        ]);

        return IssuedLetter::factory()->create([
            'letter_request_id' => $request->id,
            'reference_number' => 'EC-2026-00001',
            'snapshot' => array_merge([
                'employee_name' => 'Demo Employee',
                'job_title' => 'Machine Operator',
            ], $snapshot),
        ]);
    }

    public function test_a_valid_letter_verifies_without_signing_in(): void
    {
        $letter = $this->issuedLetter();

        $this->get(route('letters.verify', $letter->verification_token))
            ->assertOk()
            ->assertSee('Valid letter')
            ->assertSee('EC-2026-00001')
            ->assertSee('Employment Confirmation');
    }

    public function test_verification_discloses_initials_only_and_never_name_or_salary(): void
    {
        $letter = $this->issuedLetter();

        $response = $this->get(route('letters.verify', $letter->verification_token))->assertOk();

        $response->assertSee('D.E.');
        $response->assertDontSee('Demo Employee');
        $response->assertDontSee('Machine Operator');
        $response->assertDontSee('salary', false);
    }

    public function test_an_unknown_token_reports_no_match(): void
    {
        $this->get(route('letters.verify', 'not-a-real-token'))
            ->assertOk()
            ->assertSee('No matching letter');
    }

    public function test_a_revoked_letter_is_reported_as_revoked(): void
    {
        $letter = $this->issuedLetter();
        $letter->forceFill(['revoked_at' => now(), 'revoked_reason' => 'Issued in error.'])->save();

        $this->get(route('letters.verify', $letter->verification_token))
            ->assertOk()
            ->assertSee('Revoked')
            ->assertDontSee('Valid letter');
    }

    public function test_the_verification_page_is_not_indexable(): void
    {
        $letter = $this->issuedLetter();

        $this->get(route('letters.verify', $letter->verification_token))
            ->assertSee('noindex', false);
    }

    public function test_an_hr_admin_can_revoke_an_issued_letter(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $letter = $this->issuedLetter();

        $this->actingAs($admin)
            ->post(route('hr.issued-letters.revoke', $letter), ['revoked_reason' => 'Issued in error.'])
            ->assertRedirect(route('hr.approvals.show', $letter->letter_request_id));

        $letter->refresh();
        $this->assertTrue($letter->isRevoked());
        $this->assertSame($admin->id, $letter->revoked_by);
    }

    public function test_revoking_requires_a_reason(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $letter = $this->issuedLetter();

        $this->actingAs($admin)
            ->post(route('hr.issued-letters.revoke', $letter), ['revoked_reason' => ''])
            ->assertSessionHasErrors('revoked_reason');

        $this->assertFalse($letter->fresh()->isRevoked());
    }

    public function test_an_hr_officer_cannot_revoke_a_letter(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $letter = $this->issuedLetter();

        $this->actingAs($officer)
            ->post(route('hr.issued-letters.revoke', $letter), ['revoked_reason' => 'Nope.'])
            ->assertForbidden();

        $this->assertFalse($letter->fresh()->isRevoked());
    }
}
