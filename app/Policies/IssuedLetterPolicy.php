<?php

namespace App\Policies;

use App\Models\IssuedLetter;
use App\Models\User;

/**
 * Who may download or revoke an issued letter.
 *
 * Downloading mirrors the request's own visibility: the owning employee, or
 * HR staff. Revocation is an HR-admin action — it invalidates a document a
 * third party may already be relying on.
 */
class IssuedLetterPolicy
{
    public function download(User $user, IssuedLetter $letter): bool
    {
        // HR keeps access to revoked letters for their own records; the
        // employee does not, so a withdrawn letter stops circulating.
        if ($user->isHrStaff()) {
            return true;
        }

        if ($letter->isRevoked()) {
            return false;
        }

        return $user->employee_id !== null
            && (int) $user->employee_id === (int) $letter->letterRequest?->employee_id;
    }

    public function revoke(User $user, IssuedLetter $letter): bool
    {
        return $user->isHrAdmin() && ! $letter->isRevoked();
    }
}
