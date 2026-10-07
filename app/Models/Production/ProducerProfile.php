<?php

namespace App\Models\Production;

use App\Models\User;
use Database\Factories\Production\ProducerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProducerProfile extends Model
{
    /** @use HasFactory<ProducerProfileFactory> */
    use HasFactory;

    protected $attributes = ['is_active' => true, 'is_public' => false];

    protected $fillable = [
        'display_name', 'phone', 'address', 'company_name', 'description', 'is_public',
    ];

    protected $hidden = ['phone', 'address', 'description', 'logo_path'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class)->orderBy('name')->orderBy('id');
    }
}
