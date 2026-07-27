<?php

namespace App\Policies;

use App\Models\LetterRequest;
use App\Models\User;

/**
 * Skeleton policy (Phase 1). The status workflow (draft/submitted/approved…)
 * arrives in Phase 3; ownership and role boundaries are fixed now. Employees
 * only ever see their own requests — never another employee's.
 */
class LetterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // Employees see their own list; HR sees the queue. Query scoping by
        // employee_id is enforced in controllers as well (belt and braces).
        return true;
    }

    public function view(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isHrStaff() || $this->owns($user, $letterRequest);
    }

    public function create(User $user): bool
    {
        // Only active users linked to an employee record may request letters.
        return $user->is_active && $user->employee_id !== null;
    }

    public function update(User $user, LetterRequest $letterRequest): bool
    {
        // Phase 3 will restrict this to the owner while status is draft.
        return $this->owns($user, $letterRequest);
    }

    public function approve(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isHrStaff();
    }

    public function delete(User $user, LetterRequest $letterRequest): bool
    {
        // Phase 3 will allow owners to cancel drafts; nothing is deletable yet.
        return false;
    }

    private function owns(User $user, LetterRequest $letterRequest): bool
    {
        return $user->employee_id !== null
            && (int) $user->employee_id === (int) $letterRequest->employee_id;
    }
}
