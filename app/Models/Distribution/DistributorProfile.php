<?php

namespace App\Models\Distribution;

use App\Models\User;
use Database\Factories\Distribution\DistributorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributorProfile extends Model
{
    /** @use HasFactory<DistributorProfileFactory> */
    use HasFactory;

    protected $table = 'distributor_profiles';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'company_name', 'address', 'phone', 'region',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
