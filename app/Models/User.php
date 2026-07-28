<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * Deliberately excludes role, is_active and must_change_password:
     * those are only ever set explicitly by HR/admin flows, never from
     * request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    public function isHrStaff(): bool
    {
        return $this->role->isHrStaff();
    }

    public function isHrAdmin(): bool
    {
        return $this->role->isHrAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }
}
