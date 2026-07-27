<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Placeholder model — the payslips table, private-disk file handling and
 * access logging arrive in Phase 5. It exists now so PayslipPolicy can be
 * registered against it.
 */
class Payslip extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        // Populated in Phase 5 (payslips). Kept explicitly empty —
        // nothing is mass assignable until the schema exists.
    ];
}
