<?php

namespace App\Models\Production;

use App\Enums\FarmingType;
use App\Enums\FarmStatus;
use App\Enums\IrrigationType;
use Database\Factories\Production\FarmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    /** @use HasFactory<FarmFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'active', 'is_public' => false];

    protected $fillable = [
        'name', 'governorate', 'delegation', 'area_ha', 'olive_variety',
        'farming_type', 'irrigation_type', 'gps_lat', 'gps_lng', 'description', 'is_public',
    ];

    protected $hidden = ['gps_lat', 'gps_lng', 'description'];

    protected function casts(): array
    {
        return [
            'area_ha' => 'decimal:2', 'gps_lat' => 'decimal:7', 'gps_lng' => 'decimal:7',
            'farming_type' => FarmingType::class, 'irrigation_type' => IrrigationType::class,
            'status' => FarmStatus::class, 'is_public' => 'boolean',
        ];
    }

    public function producerProfile(): BelongsTo
    {
        return $this->belongsTo(ProducerProfile::class);
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    public function publicOrigin(): array
    {
        return [
            'farm_id' => $this->id, 'name' => $this->name,
            'producer' => $this->producerProfile->display_name,
            'governorate' => $this->governorate, 'delegation' => $this->delegation,
            'area_ha' => $this->area_ha, 'olive_variety' => $this->olive_variety,
            'farming_type' => $this->farming_type->label(),
            'irrigation_type' => $this->irrigation_type->label(),
        ];
    }
}
