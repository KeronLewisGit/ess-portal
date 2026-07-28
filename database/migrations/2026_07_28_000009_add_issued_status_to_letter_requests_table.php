<?php

use App\Enums\LetterRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds 'issued' to the letter_requests.status enum.
     *
     * Uses ->change() rather than raw ALTER … MODIFY so the migration works on
     * both MySQL (production) and SQLite (the test suite), which needs a table
     * rebuild to widen an enum's check constraint.
     */
    public function up(): void
    {
        $this->setStatusEnum(LetterRequestStatus::values());
    }

    public function down(): void
    {
        // Anything already issued falls back to approved before the value goes.
        DB::table('letter_requests')
            ->where('status', LetterRequestStatus::Issued->value)
            ->update(['status' => LetterRequestStatus::Approved->value]);

        $this->setStatusEnum(array_values(array_filter(
            LetterRequestStatus::values(),
            fn (string $value) => $value !== LetterRequestStatus::Issued->value,
        )));
    }

    /**
     * @param  array<int, string>  $values
     */
    private function setStatusEnum(array $values): void
    {
        Schema::table('letter_requests', function (Blueprint $table) use ($values) {
            $table->enum('status', $values)
                ->default(LetterRequestStatus::Draft->value)
                ->change();
        });
    }
};
