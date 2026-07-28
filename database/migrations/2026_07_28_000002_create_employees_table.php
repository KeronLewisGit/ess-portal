<?php

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();

            // Encrypted at rest (Eloquent `encrypted` cast). TEXT because the
            // ciphertext is much longer than the plaintext national id.
            $table->text('national_id')->nullable();

            $table->string('work_email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();

            $table->string('job_title')->nullable();

            $table->foreignId('department_id')->nullable()
                ->constrained('departments')->nullOnDelete();

            // Self-referencing manager relationship.
            $table->foreignId('manager_id')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->enum('employment_type', EmploymentType::values())
                ->default(EmploymentType::Permanent->value);
            $table->enum('employment_status', EmploymentStatus::values())
                ->default(EmploymentStatus::Active->value);

            $table->date('date_hired')->nullable();
            $table->date('date_separated')->nullable();

            // Encrypted at rest; stored as TEXT ciphertext. Kept out of any
            // serialization via the model's $hidden array.
            $table->text('annual_salary')->nullable();
            $table->string('salary_currency', 3)->nullable();

            $table->enum('pay_frequency', PayFrequency::values())
                ->default(PayFrequency::Monthly->value);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employment_status']);
            $table->index(['department_id']);
        });

        // Now that employees exists, wire the deferred FK on departments.
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_employee_id')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_employee_id']);
        });

        Schema::dropIfExists('employees');
    }
};
