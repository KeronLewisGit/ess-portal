<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates gap-free, race-safe sequential reference numbers.
 *
 * The counter row is locked with SELECT ... FOR UPDATE inside a transaction,
 * so concurrent callers serialize on the row rather than racing. Reference
 * numbers are NEVER derived from COUNT(*) or MAX(id) (both race).
 */
class DocumentSequenceService
{
    /**
     * Return the next formatted reference number for a prefix, e.g.
     * next('JL') => "JL-2026-00001".
     */
    public function next(string $prefix, ?int $year = null, int $padding = 5): string
    {
        $year ??= (int) now()->year;

        $number = $this->nextNumber($prefix, $year);

        return sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
    }

    /**
     * Increment and return the raw next number for a prefix/year, atomically.
     */
    public function nextNumber(string $prefix, ?int $year = null): int
    {
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($prefix, $year) {
            $sequence = DocumentSequence::query()
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                // Create the row, then re-fetch under lock to guard against a
                // concurrent creator (the unique constraint makes the loser
                // fall through to the locked read).
                DocumentSequence::query()->firstOrCreate(
                    ['prefix' => $prefix, 'year' => $year],
                    ['last_number' => 0],
                );

                $sequence = DocumentSequence::query()
                    ->where('prefix', $prefix)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();
            }

            $sequence->last_number++;
            $sequence->save();

            return $sequence->last_number;
        });
    }
}
