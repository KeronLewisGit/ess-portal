<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Placeholder model — the letter_requests table, status workflow, factory
 * and relationships arrive in Phase 3. It exists now so LetterRequestPolicy
 * can be registered against it.
 */
class LetterRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        // Populated in Phase 3 (letter requests). Kept explicitly empty —
        // nothing is mass assignable until the schema exists.
    ];
}
