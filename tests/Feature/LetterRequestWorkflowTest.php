<?php

namespace Tests\Feature;

use App\Enums\LetterRequestStatus;
use App\Enums\Role;
use App\Mail\LetterRequestDecisionMail;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LetterRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);
    }

    public function test_an_employee_can_submit_a_request_and_it_gets_a_reference_number(): void
    {
        Mail::fake();

        $user = $this->employeeUser();
        $type = LetterType::factory()->create(['reference_prefix' => 'EC']);

        $this->actingAs($user)
            ->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'Needed for a rental application.',
                'action' => 'submit',
            ])
            ->assertSessionHasNoErrors();

        $request = LetterRequest::firstOrFail();

        $this->assertSame(LetterRequestStatus::Submitted, $request->status);
        $this->assertSame($user->employee_id, $request->employee_id);
        $this->assertNotNull($request->submitted_at);
        $this->assertSame('EC-'.now()->year.'-00001', $request->reference_number);
    }

    public function test_a_draft_is_saved_without_burning_a_reference_number(): void
    {
        $user = $this->employeeUser();
        $type = LetterType::factory()->create();

        $this->actingAs($user)
            ->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'Thinking about it.',
                'action' => 'draft',
            ])
            ->assertSessionHasNoErrors();

        $request = LetterRequest::firstOrFail();

        $this->assertSame(LetterRequestStatus::Draft, $request->status);
        $this->assertNull($request->reference_number);
        $this->assertNull($request->submitted_at);
    }

    public function test_an_employee_cannot_view_another_employees_request(): void
    {
        $mine = $this->employeeUser();
        $theirs = LetterRequest::factory()->submitted()->create();

        $this->actingAs($mine)
            ->get(route('letter-requests.show', $theirs))
            ->assertForbidden();
    }

    public function test_the_index_only_lists_the_signed_in_employees_requests(): void
    {
        $user = $this->employeeUser();

        $mine = LetterRequest::factory()->submitted()->create(['employee_id' => $user->employee_id]);
        $theirs = LetterRequest::factory()->submitted()->create();

        $this->actingAs($user)
            ->get(route('letter-requests.index'))
            ->assertOk()
            ->assertSee($mine->reference_number)
            ->assertDontSee($theirs->reference_number);
    }

    public function test_a_submitted_request_can_no_longer_be_edited_by_its_owner(): void
    {
        $user = $this->employeeUser();
        $request = LetterRequest::factory()->submitted()->create(['employee_id' => $user->employee_id]);

        $this->actingAs($user)
            ->get(route('letter-requests.edit', $request))
            ->assertForbidden();
    }

    public function test_an_employee_can_withdraw_a_pending_request(): void
    {
        $user = $this->employeeUser();
        $request = LetterRequest::factory()->submitted()->create(['employee_id' => $user->employee_id]);

        $this->actingAs($user)
            ->delete(route('letter-requests.cancel', $request))
            ->assertRedirect(route('letter-requests.index'));

        $this->assertSame(LetterRequestStatus::Cancelled, $request->fresh()->status);
    }

    public function test_a_decided_request_can_no_longer_be_withdrawn(): void
    {
        $user = $this->employeeUser();
        $request = LetterRequest::factory()->approved()->create(['employee_id' => $user->employee_id]);

        $this->actingAs($user)
            ->delete(route('letter-requests.cancel', $request))
            ->assertForbidden();
    }

    public function test_a_user_with_no_employee_record_cannot_request_a_letter(): void
    {
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => null]);
        $type = LetterType::factory()->create();

        $this->actingAs($user)
            ->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'Please.',
            ])
            ->assertForbidden();
    }

    public function test_an_inactive_letter_type_cannot_be_requested(): void
    {
        $user = $this->employeeUser();
        $type = LetterType::factory()->inactive()->create();

        $this->actingAs($user)
            ->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'Needed.',
            ])
            ->assertSessionHasErrors('letter_type_id');

        $this->assertDatabaseCount('letter_requests', 0);
    }

    public function test_reference_numbers_are_sequential_per_letter_type_prefix(): void
    {
        Mail::fake();

        $user = $this->employeeUser();
        $ec = LetterType::factory()->create(['reference_prefix' => 'EC']);
        $bl = LetterType::factory()->create(['reference_prefix' => 'BL']);

        foreach ([$ec, $ec, $bl] as $type) {
            $this->actingAs($user)->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'Needed.',
                'action' => 'submit',
            ])->assertSessionHasNoErrors();
        }

        $year = now()->year;
        $references = LetterRequest::orderBy('id')->pluck('reference_number')->all();

        $this->assertSame(["EC-{$year}-00001", "EC-{$year}-00002", "BL-{$year}-00001"], $references);
    }

    public function test_submitting_is_rate_limited_per_day(): void
    {
        Mail::fake();
        config(['ess.rate_limits.letter_requests_per_day' => 2]);

        $user = $this->employeeUser();
        $type = LetterType::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => "Request {$i}",
                'action' => 'submit',
            ])->assertSessionHasNoErrors();
        }

        // The third within the same day is refused by the throttle middleware.
        $this->actingAs($user)
            ->post(route('letter-requests.store'), [
                'letter_type_id' => $type->id,
                'purpose' => 'One too many',
                'action' => 'submit',
            ])
            ->assertStatus(429);

        $this->assertDatabaseCount('letter_requests', 2);
    }

    public function test_the_decision_email_is_queued_to_the_employee(): void
    {
        Mail::fake();

        $employee = Employee::factory()->create(['work_email' => 'requester@example.com']);
        $request = LetterRequest::factory()->submitted()->create(['employee_id' => $employee->id]);
        $hr = User::factory()->role(Role::HrAdmin)->create();

        $this->actingAs($hr)
            ->post(route('hr.approvals.approve', $request))
            ->assertRedirect(route('hr.approvals.index'));

        Mail::assertQueued(
            LetterRequestDecisionMail::class,
            fn ($mail) => $mail->hasTo('requester@example.com')
        );
    }
}
