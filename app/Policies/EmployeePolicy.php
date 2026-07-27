<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Skeleton policy (Phase 1). Rules will be refined in Phase 2 when the
 * employee master exists, but the authorisation posture is set now:
 * an employee may only ever read their own record; management is HR-only.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHrStaff();
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->isHrStaff()
            || ($user->employee_id !== null && (int) $user->employee_id === (int) $employee->getKey());
    }

    public function create(User $user): bool
    {
        return $user->isHrAdmin();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isHrAdmin();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isHrAdmin();
    }
}
