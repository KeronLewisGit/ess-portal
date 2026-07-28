<?php

use App\Enums\LetterRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();

            /*
             * Assigned by DocumentSequenceService on submission (not on draft
             * creation) so abandoned drafts don't burn reference numbers.
             */
            $table->string('reference_number')->nullable()->unique();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Types are never hard-deleted while requests reference them.
            $table->foreignId('letter_type_id')->constrained('letter_types')->restrictOnDelete();

            $table->enum('status', LetterRequestStatus::values())
                ->default(LetterRequestStatus::Draft->value);

            /*
             * Employee opt-in to stating salary. The salary VALUE is never
             * copied here — it is read from the encrypted employee field at
             * generation time (Phase 4). Approving one of these is restricted
             * to hr_admin/super_admin.
             */
            $table->boolean('include_salary')->default(false);

            // Who the letter is addressed to, and why it is needed.
            $table->string('addressed_to')->nullable();
            $table->text('purpose')->nullable();

            $table->timestamp('submitted_at')->nullable();

            // Decision trail. decision_notes carries the rejection reason.
            $table->foreignId('decided_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // The approval queue filters on status; employees list their own.
            $table->index(['status', 'submitted_at']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
    }
};
