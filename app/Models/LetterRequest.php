<?php

namespace App\Models;

use App\Enums\LetterRequestStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\LetterRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterRequest extends Model
{
    /** @use HasFactory<LetterRequestFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Only the fields an employee fills in are mass assignable. Status,
     * reference_number and the decision trail are set exclusively by
     * LetterRequestService, never from request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'letter_type_id',
        'include_salary',
        'addressed_to',
        'purpose',
    ];

    protected function casts(): array
    {
        return [
            'status' => LetterRequestStatus::class,
            'include_salary' => 'boolean',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * The generated PDF, once the request has been issued (Phase 4).
     */
    public function issuedLetter(): HasOne
    {
        return $this->hasOne(IssuedLetter::class);
    }

    /**
     * The HR approval queue: submitted requests, oldest first.
     *
     * @param  Builder<LetterRequest>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LetterRequestStatus::Submitted)
            ->orderBy('submitted_at');
    }

    /**
     * Scope to a single employee — used for the employee-facing list so the
     * query itself can never return someone else's request.
     *
     * @param  Builder<LetterRequest>  $query
     */
    public function scopeForEmployee(Builder $query, ?int $employeeId): Builder
    {
        // A null employee_id must match nothing, not everything.
        return $employeeId === null
            ? $query->whereRaw('1 = 0')
            : $query->where('employee_id', $employeeId);
    }

    /**
     * Approving a salary-disclosing letter is restricted to HR admins.
     */
    public function disclosesSalary(): bool
    {
        return $this->include_salary;
    }
}
