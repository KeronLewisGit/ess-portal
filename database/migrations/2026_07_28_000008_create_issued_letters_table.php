<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_letters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_request_id')->constrained('letter_requests')->cascadeOnDelete();

            /*
             * Copied from the request at issue time. An issued letter is an
             * immutable document: it keeps its own reference even if the
             * request is later touched.
             */
            $table->string('reference_number')->unique();

            /*
             * Public, unguessable handle for the verification page. Separate
             * from reference_number so the sequential reference can appear on
             * the letter without making every other letter enumerable.
             */
            $table->string('verification_token', 64)->unique();

            // Path on the PRIVATE disk. Never public, never storage:link'ed.
            $table->string('file_path');
            // SHA-256 of the stored PDF, so tampering on disk is detectable.
            $table->string('file_hash', 64);
            $table->unsignedInteger('file_size');

            /*
             * The facts as they stood when the letter was issued. A letter
             * must keep saying what it said even if the employee later
             * changes job title. Deliberately excludes salary — the figure
             * lives only inside the PDF on the private disk.
             */
            $table->json('snapshot');

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');

            // Revocation makes the public verification page report "revoked".
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoked_reason')->nullable();

            $table->timestamps();

            $table->index('letter_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_letters');
    }
};
