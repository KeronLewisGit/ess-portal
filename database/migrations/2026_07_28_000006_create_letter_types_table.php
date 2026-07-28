<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();

            /*
             * Body template with {{ placeholder }} tokens, rendered against
             * the employee record when the PDF is produced (Phase 4). Held in
             * the database rather than as Blade files so HR can edit wording
             * without a deployment.
             */
            $table->text('body_template');

            // Feeds DocumentSequenceService, e.g. "JL" => JL-2026-00001.
            $table->string('reference_prefix', 10)->default('LTR');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_types');
    }
};
