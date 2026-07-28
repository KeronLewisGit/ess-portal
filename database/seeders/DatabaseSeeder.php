<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Idempotent: safe to run repeatedly.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        // Demo accounts, one per role. Local/demo use only — password is
        // "password"; production provisioning happens through HR flows.
        $demoUsers = [
            ['name' => 'Demo Employee', 'email' => 'employee@example.com', 'role' => Role::Employee],
            ['name' => 'Demo HR Officer', 'email' => 'hr.officer@example.com', 'role' => Role::HrOfficer],
            ['name' => 'Demo HR Admin', 'email' => 'hr.admin@example.com', 'role' => Role::HrAdmin],
            ['name' => 'Demo Super Admin', 'email' => 'super.admin@example.com', 'role' => Role::SuperAdmin],
        ];

        foreach ($demoUsers as $demo) {
            // forceFill: role/is_active/must_change_password are intentionally
            // NOT mass assignable, so they are set explicitly here.
            User::query()
                ->firstOrNew(['email' => $demo['email']])
                ->fill([
                    'name' => $demo['name'],
                    'email' => $demo['email'],
                    'password' => Hash::make('password'),
                ])
                ->forceFill([
                    'role' => $demo['role'],
                    'is_active' => true,
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ])
                ->save();
        }

        // Employee master (Phase 2). Departments first — employees FK them.
        $this->call([
            DepartmentSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
