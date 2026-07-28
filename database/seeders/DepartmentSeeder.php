<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Idempotent: keyed on the unique code.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Production', 'code' => 'PROD'],
            ['name' => 'Quality Assurance', 'code' => 'QA'],
            ['name' => 'Maintenance', 'code' => 'MAINT'],
            ['name' => 'Logistics', 'code' => 'LOG'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']],
            );
        }
    }
}
