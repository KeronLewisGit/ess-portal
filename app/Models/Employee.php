<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Placeholder model — the employees table, relationships, encrypted casts
 * (national_id, annual_salary) and factory arrive in Phase 2. It exists now
 * so EmployeePolicy can be registered against it.
 */
class Employee extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        // Populated in Phase 2 (employee master). Kept explicitly empty —
        // nothing is mass assignable until the schema exists.
    ];

    /**
     * Salary data must never leak through serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'annual_salary',
        'national_id',
    ];
}
