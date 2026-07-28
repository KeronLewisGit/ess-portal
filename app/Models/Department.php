<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'head_employee_id',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }
}
