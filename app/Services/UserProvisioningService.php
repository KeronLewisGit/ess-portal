<?php

namespace App\Services;

use App\Enums\Role;
use App\Mail\EmployeeInvitationMail;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Provisions login accounts for employees: creates the User, forces a
 * password change on first login, and emails a set-password invitation.
 */
class UserProvisioningService
{
    /**
     * Create (or re-invite) a user account for an employee and email an
     * invitation with a password-set link.
     */
    public function provision(Employee $employee, Role $role): User
    {
        $user = User::firstOrNew(['employee_id' => $employee->id]);

        // Fall back to matching by work email for accounts created before the
        // employee link existed (e.g. Phase 1 demo users).
        if (! $user->exists) {
            $existing = User::where('email', $employee->work_email)->first();
            if ($existing !== null) {
                $user = $existing;
            }
        }

        $user->fill([
            'name' => $employee->full_name,
            'email' => $employee->work_email,
            // Unknown random password; the invite link sets the real one.
            'password' => Str::random(40),
        ]);

        $user->forceFill([
            'employee_id' => $employee->id,
            'role' => $role,
            'is_active' => true,
            'must_change_password' => true,
        ])->save();

        $this->sendInvitation($user);

        return $user;
    }

    public function sendInvitation(User $user): void
    {
        // A password-reset token doubles as a secure, expiring set-password
        // link for the invitation.
        $token = Password::broker()->createToken($user);

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], absolute: false));

        Mail::to($user->email)->queue(new EmployeeInvitationMail($user, $resetUrl));
    }
}
