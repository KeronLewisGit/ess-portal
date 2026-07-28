<?php

namespace App\Policies;

use App\Models\LetterType;
use App\Models\User;

/**
 * Letter template authorisation.
 *
 * All HR staff may browse the templates (they need to know what exists when
 * working the approval queue), but editing the wording of an official company
 * letter is an HR-admin action.
 */
class LetterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHrStaff();
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return $user->isHrStaff();
    }

    public function create(User $user): bool
    {
        return $user->isHrAdmin();
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * A template that has already been used is never deleted — issued letters
     * must keep resolving their type. Deactivate it instead.
     */
    public function delete(User $user, LetterType $letterType): bool
    {
        return $user->isHrAdmin() && $letterType->letterRequests()->doesntExist();
    }
}
