<?php

namespace Tests\Feature\Hr;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function hrAdmin(): User
    {
        return User::factory()->role(Role::HrAdmin)->create();
    }

    public function test_hr_admin_can_view_the_employee_index(): void
    {
        Employee::factory()->count(3)->create();

        $this->actingAs($this->hrAdmin())
            ->get(route('hr.employees.index'))
            ->assertOk()
            ->assertSee('Employees');
    }

    public function test_hr_admin_can_create_an_employee(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->hrAdmin())->post(route('hr.employees.store'), [
            'employee_code' => 'EMP7777',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'work_email' => 'ada@example.com',
            'national_id' => 'ID-SECRET-1',
            'annual_salary' => '75000',
            'department_id' => $department->id,
            'employment_type' => 'permanent',
            'employment_status' => 'active',
            'pay_frequency' => 'monthly',
            'salary_currency' => 'USD',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP7777',
            'work_email' => 'ada@example.com',
        ]);

        // Sensitive fields stored encrypted (not as plaintext).
        $employee = Employee::where('employee_code', 'EMP7777')->first();
        $this->assertSame('75000', (string) $employee->annual_salary);
        $this->assertSame('ID-SECRET-1', $employee->national_id);
        $this->assertDatabaseMissing('employees', ['national_id' => 'ID-SECRET-1']);
    }

    public function test_hr_admin_can_update_an_employee(): void
    {
        $employee = Employee::factory()->create(['job_title' => 'Operator']);

        $this->actingAs($this->hrAdmin())
            ->put(route('hr.employees.update', $employee), array_merge($employee->only([
                'employee_code', 'first_name', 'last_name', 'work_email',
            ]), [
                'job_title' => 'Senior Operator',
                'employment_type' => $employee->employment_type->value,
                'employment_status' => $employee->employment_status->value,
                'pay_frequency' => $employee->pay_frequency->value,
            ]))
            ->assertRedirect();

        $this->assertSame('Senior Operator', $employee->fresh()->job_title);
    }

    public function test_hr_admin_can_bulk_deactivate_employees(): void
    {
        $employees = Employee::factory()->count(3)->create(['employment_status' => EmploymentStatus::Active]);

        $this->actingAs($this->hrAdmin())
            ->post(route('hr.employees.bulk-deactivate'), ['ids' => $employees->pluck('id')->all()])
            ->assertRedirect();

        foreach ($employees as $employee) {
            $this->assertSame(EmploymentStatus::Separated, $employee->fresh()->employment_status);
        }
    }

    public function test_plain_employee_cannot_reach_employee_management_routes(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->role(Role::Employee)->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->get(route('hr.employees.index'))->assertForbidden();
        $this->actingAs($user)->get(route('hr.employees.create'))->assertForbidden();
        $this->actingAs($user)->get(route('hr.employees.show', $employee))->assertForbidden();
        $this->actingAs($user)->get(route('hr.employees.import.create'))->assertForbidden();
        $this->actingAs($user)->post(route('hr.employees.store'), [])->assertForbidden();
    }

    public function test_hr_officer_can_view_but_not_create_employees(): void
    {
        $officer = User::factory()->role(Role::HrOfficer)->create();

        $this->actingAs($officer)->get(route('hr.employees.index'))->assertOk();
        // Create is HR-admin only; the policy blocks the officer.
        $this->actingAs($officer)->get(route('hr.employees.create'))->assertForbidden();
    }
}
