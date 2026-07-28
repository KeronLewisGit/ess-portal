<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Separated = 'separated';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Separated => 'Separated',
        };
    }

    /**
     * Tailwind badge classes for status display.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::OnLeave => 'bg-yellow-100 text-yellow-800',
            self::Separated => 'bg-gray-200 text-gray-700',
        };
    }
}
