<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FK constraint to employees is added in Phase 2 when the
            // employees table exists; the column is created now so policies
            // and query scoping can rely on it from the start.
            $table->unsignedBigInteger('employee_id')->nullable()->index()->after('id');
            $table->enum('role', Role::values())->default(Role::Employee->value)->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'role',
                'is_active',
                'must_change_password',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
