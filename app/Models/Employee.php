<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Models\Concerns\Auditable;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Mass-assignable attributes. national_id and annual_salary are included
     * so the service layer can assign them explicitly from validated input,
     * but they are NEVER placed in a Blade view or serialized (see $hidden).
     * Controllers pass only validated data through the EmployeeService.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'middle_name',
        'national_id',
        'work_email',
        'personal_email',
        'phone',
        'job_title',
        'department_id',
        'manager_id',
        'employment_type',
        'employment_status',
        'date_hired',
        'date_separated',
        'annual_salary',
        'salary_currency',
        'pay_frequency',
    ];

    /**
     * Sensitive salary/identity data is never serialized to JSON and is
     * redacted from the audit trail by the Auditable trait.
     *
     * @var list<string>
     */
    protected $hidden = [
        'national_id',
        'annual_salary',
    ];

    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'annual_salary' => 'encrypted',
            'date_hired' => 'date',
            'date_separated' => 'date',
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
            'pay_frequency' => PayFrequency::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /**
     * Employee's initials, e.g. for the public letter-verification route.
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1).substr($this->last_name, 0, 1)
        );
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('employee_code', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('work_email', 'like', $like)
                ->orWhere('job_title', 'like', $like);
        });
    }
}
