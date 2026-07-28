<?php

namespace App\Policies;

use App\Models\LetterRequest;
use App\Models\User;

/**
 * Letter request authorisation (Phase 3 — status workflow now in place).
 *
 * - Employees only ever see and act on their OWN requests, resolved via the
 *   session user's employee_id, never a request-supplied id.
 * - Any HR staff may approve or reject a submitted request…
 * - …EXCEPT one that discloses salary, which only an HR admin may approve.
 */
class LetterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // Employees see their own list; HR sees the queue. The queries
        // themselves are scoped as well (belt and braces).
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

    /**
     * Drafts are editable by their owner only, and only while still a draft.
     */
    public function update(User $user, LetterRequest $letterRequest): bool
    {
        return $this->owns($user, $letterRequest)
            && $letterRequest->status->isEditable();
    }

    /**
     * Submit a draft for approval.
     */
    public function submit(User $user, LetterRequest $letterRequest): bool
    {
        return $this->owns($user, $letterRequest)
            && $letterRequest->status->isEditable();
    }

    /**
     * Withdraw one's own request while it is still a draft or pending.
     */
    public function cancel(User $user, LetterRequest $letterRequest): bool
    {
        return $this->owns($user, $letterRequest)
            && $letterRequest->status->isCancellable();
    }

    /**
     * Approving a letter that states salary discloses encrypted pay data, so
     * it is restricted to HR admins even though any HR staff may approve an
     * ordinary letter.
     */
    public function approve(User $user, LetterRequest $letterRequest): bool
    {
        if (! $user->isHrStaff() || ! $letterRequest->status->isPending()) {
            return false;
        }

        return $letterRequest->disclosesSalary()
            ? $user->isHrAdmin()
            : true;
    }

    /**
     * Rejecting discloses nothing, so any HR staff may reject any pending
     * request — including a salary one they could not have approved.
     */
    public function reject(User $user, LetterRequest $letterRequest): bool
    {
        return $user->isHrStaff() && $letterRequest->status->isPending();
    }

    /**
     * Hard deletion is never allowed — requests are cancelled, and the
     * history is retained for audit.
     */
    public function delete(User $user, LetterRequest $letterRequest): bool
    {
        return false;
    }

    private function owns(User $user, LetterRequest $letterRequest): bool
    {
        return $user->employee_id !== null
            && (int) $user->employee_id === (int) $letterRequest->employee_id;
    }
}
