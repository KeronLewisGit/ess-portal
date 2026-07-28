<?php

namespace Tests\Feature;

use App\Enums\LetterRequestStatus;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade templates only fail at render time, so every Phase 3 screen is
 * fetched at least once. Behaviour is covered by the workflow tests.
 */
class LetterViewsRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_employee_facing_letter_screen_renders(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
        LetterType::factory()->create();

        $draft = LetterRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => LetterRequestStatus::Draft,
        ]);

        $rejected = LetterRequest::factory()->submitted()->create([
            'employee_id' => $employee->id,
            'status' => LetterRequestStatus::Rejected,
            'decision_notes' => 'Missing supporting detail.',
            'decided_at' => now(),
        ]);

        $this->actingAs($user);

        $this->get(route('letter-requests.index'))->assertOk();
        $this->get(route('letter-requests.create'))->assertOk();
        $this->get(route('letter-requests.show', $draft))->assertOk();
        $this->get(route('letter-requests.edit', $draft))->assertOk();

        // The rejection reason is surfaced to the employee.
        $this->get(route('letter-requests.show', $rejected))
            ->assertOk()
            ->assertSee('Missing supporting detail.');
    }

    public function test_every_hr_facing_letter_screen_renders(): void
    {
        $admin = User::factory()->role(Role::HrAdmin)->create();
        $type = LetterType::factory()->create();

        $pending = LetterRequest::factory()->submitted()->withSalary()->create();
        $decided = LetterRequest::factory()->approved()->create();

        $this->actingAs($admin);

        $this->get(route('hr.approvals.index'))->assertOk();
        $this->get(route('hr.approvals.index', ['status' => 'approved']))->assertOk();
        $this->get(route('hr.approvals.show', $pending))->assertOk();
        $this->get(route('hr.approvals.show', $decided))->assertOk();

        $this->get(route('hr.letter-types.index'))->assertOk();
        $this->get(route('hr.letter-types.create'))->assertOk();
        $this->get(route('hr.letter-types.edit', $type))->assertOk();
    }

    public function test_an_employee_with_no_record_is_told_why_they_cannot_request(): void
    {
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => null]);

        $this->actingAs($user)
            ->get(route('letter-requests.index'))
            ->assertOk()
            ->assertSee("isn't linked to an employee record", false);
    }
}
