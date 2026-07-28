<?php

namespace Tests\Feature\Hr;

use App\Enums\LetterRequestStatus;
use App\Enums\Role;
use App\Jobs\GenerateLetterPdf;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LetterApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // These tests are about the approval DECISION. Approving also queues
        // PDF generation (Phase 4), which would otherwise run inline on the
        // sync queue and carry the status straight through to `issued` —
        // generation itself is covered by LetterGenerationTest.
        Bus::fake([GenerateLetterPdf::class]);
    }

    public function test_an_hr_officer_can_approve_an_ordinary_request(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $request = LetterRequest::factory()->submitted()->create();

        $this->actingAs($officer)
            ->post(route('hr.approvals.approve', $request))
            ->assertRedirect(route('hr.approvals.index'));

        $request->refresh();
        $this->assertSame(LetterRequestStatus::Approved, $request->status);
        $this->assertSame($officer->id, $request->decided_by);
        $this->assertNotNull($request->decided_at);
    }

    public function test_an_hr_officer_cannot_approve_a_request_that_states_salary(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $request = LetterRequest::factory()->submitted()->withSalary()->create();

        $this->actingAs($officer)
            ->post(route('hr.approvals.approve', $request))
            ->assertForbidden();

        $this->assertSame(LetterRequestStatus::Submitted, $request->fresh()->status);
    }

    public function test_an_hr_admin_can_approve_a_request_that_states_salary(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $request = LetterRequest::factory()->submitted()->withSalary()->create();

        $this->actingAs($admin)
            ->post(route('hr.approvals.approve', $request))
            ->assertRedirect(route('hr.approvals.index'));

        $this->assertSame(LetterRequestStatus::Approved, $request->fresh()->status);
    }

    public function test_an_hr_officer_may_still_reject_a_salary_request(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $request = LetterRequest::factory()->submitted()->withSalary()->create();

        $this->actingAs($officer)
            ->post(route('hr.approvals.reject', $request), ['decision_notes' => 'Not supported for contractors.'])
            ->assertRedirect(route('hr.approvals.index'));

        $request->refresh();
        $this->assertSame(LetterRequestStatus::Rejected, $request->status);
        $this->assertSame('Not supported for contractors.', $request->decision_notes);
    }

    public function test_a_rejection_requires_a_reason(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $request = LetterRequest::factory()->submitted()->create();

        $this->actingAs($officer)
            ->post(route('hr.approvals.reject', $request), ['decision_notes' => ''])
            ->assertSessionHasErrors('decision_notes');

        $this->assertSame(LetterRequestStatus::Submitted, $request->fresh()->status);
    }

    public function test_an_already_decided_request_cannot_be_decided_again(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $request = LetterRequest::factory()->approved()->create();

        $this->actingAs($admin)
            ->post(route('hr.approvals.approve', $request))
            ->assertForbidden();
    }

    public function test_a_draft_never_appears_in_the_approval_queue(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();

        $draft = LetterRequest::factory()->create(['status' => LetterRequestStatus::Draft]);
        $pending = LetterRequest::factory()->submitted()->create();

        $this->actingAs($officer)
            ->get(route('hr.approvals.index'))
            ->assertOk()
            ->assertSee($pending->reference_number)
            ->assertDontSee($draft->purpose);
    }

    public function test_a_plain_employee_cannot_reach_the_approval_queue(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        $request = LetterRequest::factory()->submitted()->create();

        $this->actingAs($user)->get(route('hr.approvals.index'))->assertForbidden();
        $this->actingAs($user)->post(route('hr.approvals.approve', $request))->assertForbidden();
    }

    public function test_hr_staff_can_browse_templates_but_only_admins_can_write_them(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $admin = User::factory()->role(Role::HrAdmin)->create();

        $this->actingAs($officer)->get(route('hr.letter-types.index'))->assertOk();
        $this->actingAs($officer)->get(route('hr.letter-types.create'))->assertForbidden();

        $this->actingAs($admin)
            ->post(route('hr.letter-types.store'), [
                'name' => 'Tenancy Letter',
                'code' => 'tenancy',
                'body_template' => 'This confirms {{employee_name}} works here.',
                'reference_prefix' => 'tl',
                'is_active' => '1',
            ])
            ->assertRedirect(route('hr.letter-types.index'));

        // Code and prefix are uppercased on the way in.
        $this->assertDatabaseHas('letter_types', ['code' => 'TENANCY', 'reference_prefix' => 'TL']);
    }

    public function test_a_template_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $type = LetterType::factory()->create();
        LetterRequest::factory()->submitted()->create(['letter_type_id' => $type->id]);

        $this->actingAs($admin)
            ->delete(route('hr.letter-types.destroy', $type))
            ->assertForbidden();

        $this->assertDatabaseHas('letter_types', ['id' => $type->id]);
    }
}
