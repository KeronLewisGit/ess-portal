<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\LetterTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    /** @use HasFactory<LetterTypeFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'body_template',
        'reference_prefix',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function letterRequests(): HasMany
    {
        return $this->hasMany(LetterRequest::class);
    }

    /**
     * @param  Builder<LetterType>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Placeholder tokens a body_template may contain. Documented in the
     * template editor; substitution itself happens at generation time
     * (Phase 4), which is the only point the salary value is read.
     *
     * @var array<string, string>
     */
    public const PLACEHOLDERS = [
        '{{employee_name}}' => "The employee's full name",
        '{{employee_code}}' => 'Employee code, e.g. EMP0001',
        '{{job_title}}' => 'Current job title',
        '{{department}}' => 'Department name',
        '{{date_hired}}' => 'Date the employee was hired',
        '{{employment_type}}' => 'Permanent, contract, etc.',
        '{{employment_status}}' => 'Active, on leave, separated',
        '{{salary}}' => 'Annual salary — rendered only if the employee opted in',
        '{{company_name}}' => 'Company name from settings',
        '{{company_address}}' => 'Company address from settings',
        '{{addressed_to}}' => 'Who the letter is addressed to',
        '{{reference_number}}' => 'Letter reference, e.g. JL-2026-00001',
        '{{issue_date}}' => 'Date the letter is issued',
    ];
}
