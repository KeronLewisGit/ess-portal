<?php

namespace App\Enums;

enum LetterRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

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
            self::Draft => 'Draft',
            self::Submitted => 'Awaiting approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-200 text-gray-700',
            self::Submitted => 'bg-yellow-100 text-yellow-800',
            self::Approved => 'bg-green-100 text-green-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-gray-100 text-gray-500',
        };
    }

    /**
     * Statuses the owning employee can still edit.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Statuses the owning employee can still withdraw.
     */
    public function isCancellable(): bool
    {
        return match ($this) {
            self::Draft, self::Submitted => true,
            default => false,
        };
    }

    /**
     * Sitting in the HR approval queue, awaiting a decision.
     */
    public function isPending(): bool
    {
        return $this === self::Submitted;
    }

    /**
     * A decision has been made and the request is closed to further action.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Approved, self::Rejected, self::Cancelled => true,
            default => false,
        };
    }
}
