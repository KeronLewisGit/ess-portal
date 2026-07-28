<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\EmployeeInvitationMail;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_provisions_a_user_and_queues_an_invitation(): void
    {
        Mail::fake();

        $admin = User::factory()->role(Role::HrAdmin)->create();
        $employee = Employee::factory()->create(['work_email' => 'newbie@example.com']);

        $this->actingAs($admin)
            ->post(route('hr.employees.provision-user', $employee), ['role' => Role::Employee->value])
            ->assertRedirect(route('hr.employees.show', $employee));

        $user = User::where('employee_id', $employee->id)->first();
        $this->assertNotNull($user);
        $this->assertSame('newbie@example.com', $user->email);
        $this->assertTrue($user->must_change_password);
        $this->assertSame(Role::Employee, $user->role);

        Mail::assertQueued(EmployeeInvitationMail::class, fn ($mail) => $mail->hasTo('newbie@example.com'));
    }

    public function test_a_user_flagged_for_password_change_is_forced_to_the_change_screen(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_setting_a_new_password_clears_the_flag(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'password' => 'new-secure-password-123',
                'password_confirmation' => 'new-secure-password-123',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('new-secure-password-123', $user->password));
    }

    public function test_an_hr_admin_cannot_provision_a_super_admin(): void
    {
        Mail::fake();

        $admin = User::factory()->role(Role::HrAdmin)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)
            ->post(route('hr.employees.provision-user', $employee), ['role' => Role::SuperAdmin->value])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['employee_id' => $employee->id]);
        Mail::assertNothingQueued();
    }

    public function test_a_super_admin_may_provision_a_super_admin(): void
    {
        Mail::fake();

        $admin = User::factory()->role(Role::SuperAdmin)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)
            ->post(route('hr.employees.provision-user', $employee), ['role' => Role::SuperAdmin->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(Role::SuperAdmin, User::where('employee_id', $employee->id)->first()?->role);
    }

    public function test_a_non_admin_cannot_provision_users(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();
        $employee = Employee::factory()->create();

        $this->actingAs($officer)
            ->post(route('hr.employees.provision-user', $employee), ['role' => Role::Employee->value])
            ->assertForbidden();
    }
}
