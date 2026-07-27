<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;

/**
 * Skeleton policy (Phase 1). Payslips are the most sensitive asset in the
 * system: an employee may only ever access their own payslip, and the
 * employee identity is always derived from the authenticated session —
 * never from request input. Full retrieval flow arrives in Phase 5.
 */
class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Payslip $payslip): bool
    {
        return $this->owns($user, $payslip) || $user->isHrAdmin();
    }

    public function download(User $user, Payslip $payslip): bool
    {
        return $this->view($user, $payslip);
    }

    public function manage(User $user): bool
    {
        // Upload / publish / reconcile (Phase 5 HR screens).
        return $user->isHrAdmin();
    }

    private function owns(User $user, Payslip $payslip): bool
    {
        return $user->is_active
            && $user->employee_id !== null
            && (int) $user->employee_id === (int) $payslip->employee_id;
    }
}
