<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Probation = 'probation';
    case Temporary = 'temporary';

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
            self::Permanent => 'Permanent',
            self::Contract => 'Contract',
            self::Probation => 'Probation',
            self::Temporary => 'Temporary',
        };
    }
}
