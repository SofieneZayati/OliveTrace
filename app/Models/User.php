<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Production\ProducerProfile;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['role' => 'consumer', 'is_active' => true];

    /**
     * The attributes that are mass assignable.
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

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isActiveAdmin(): bool
    {
        return $this->is_active && $this->isAdmin();
    }

    public function isActiveProducer(): bool
    {
        return $this->is_active && $this->hasRole(Role::Producer);
    }

    public function isActiveMiller(): bool
    {
        return $this->is_active && $this->hasRole(Role::Miller);
    }

    public function producerProfile(): HasOne
    {
        return $this->hasOne(ProducerProfile::class);
    }

    public function mill(): HasOne
    {
        return $this->hasOne(Mill::class);
    }

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
        ];
    }
}
