<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wires the users.employee_id FK that was intentionally deferred in
     * Phase 1 (the column was created then; the employees table now exists).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
    }
};
