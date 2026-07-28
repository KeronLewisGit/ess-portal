<?php

namespace App\Enums;

enum PayFrequency: string
{
    case Monthly = 'monthly';
    case Fortnightly = 'fortnightly';
    case Weekly = 'weekly';

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
            self::Monthly => 'Monthly',
            self::Fortnightly => 'Fortnightly',
            self::Weekly => 'Weekly',
        };
    }

    /**
     * Number of pay periods in a year, used to derive per-period figures.
     */
    public function periodsPerYear(): int
    {
        return match ($this) {
            self::Monthly => 12,
            self::Fortnightly => 26,
            self::Weekly => 52,
        };
    }
}
