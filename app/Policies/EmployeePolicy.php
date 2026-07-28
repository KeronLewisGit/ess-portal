<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Employee master authorisation.
 *
 * - All HR staff may browse/view employee records.
 * - Only HR admins (hr_admin, super_admin) may create, edit, deactivate,
 *   import, or delete — employee master data (incl. salary) is sensitive.
 * - A plain employee may only ever view their OWN record, resolved via
 *   their linked employee_id (never a request-supplied id).
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHrStaff();
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isHrStaff()) {
            return true;
        }

        return $user->employee_id !== null
            && (int) $user->employee_id === (int) $employee->getKey();
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

    /**
     * Bulk deactivate and CSV/XLSX import.
     */
    public function manage(User $user): bool
    {
        return $user->isHrAdmin();
    }

    /**
     * Provision (or re-invite) a login account for an employee.
     */
    public function provisionUser(User $user, Employee $employee): bool
    {
        return $user->isHrAdmin();
    }
}
