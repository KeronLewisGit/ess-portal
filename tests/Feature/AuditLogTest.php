<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_employee_writes_a_created_audit_log(): void
    {
        $employee = Employee::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'action' => 'created',
        ]);
    }

    public function test_updating_captures_old_and_new_values(): void
    {
        $actor = User::factory()->create();
        $employee = Employee::factory()->create(['job_title' => 'Operator']);

        $this->actingAs($actor);
        $employee->update(['job_title' => 'Supervisor']);

        $log = AuditLog::where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame('Operator', $log->old_values['job_title']);
        $this->assertSame('Supervisor', $log->new_values['job_title']);
    }

    public function test_sensitive_attributes_are_redacted_in_the_audit_trail(): void
    {
        $employee = Employee::factory()->create(['annual_salary' => 90000, 'national_id' => 'ID-XYZ']);

        $log = AuditLog::where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->where('action', 'created')
            ->first();

        $this->assertSame('********', $log->new_values['annual_salary']);
        $this->assertSame('********', $log->new_values['national_id']);
    }
}
