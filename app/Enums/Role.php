<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'employee';
    case HrOfficer = 'hr_officer';
    case HrAdmin = 'hr_admin';
    case SuperAdmin = 'super_admin';

    /**
     * All enum values, e.g. for migration / validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::HrOfficer => 'HR Officer',
            self::HrAdmin => 'HR Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    /**
     * Roles allowed into the HR area of the portal.
     */
    public function isHrStaff(): bool
    {
        return match ($this) {
            self::HrOfficer, self::HrAdmin, self::SuperAdmin => true,
            default => false,
        };
    }

    /**
     * Roles allowed to administer HR master data (employees, templates, payslips).
     */
    public function isHrAdmin(): bool
    {
        return match ($this) {
            self::HrAdmin, self::SuperAdmin => true,
            default => false,
        };
    }

    /**
     * Roles this role may grant when provisioning a login account. Only a
     * super admin can mint another super admin — otherwise an hr_admin could
     * escalate itself by provisioning a super admin account it controls.
     *
     * @return array<int, self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SuperAdmin => self::cases(),
            self::HrAdmin => [self::Employee, self::HrOfficer, self::HrAdmin],
            default => [],
        };
    }
}
