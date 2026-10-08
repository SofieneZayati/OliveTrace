<?php

namespace App\Models\Distribution;

use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use Database\Factories\Distribution\ShipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $table = 'shipments';

    protected $attributes = [
        'status' => 'planned',
    ];

    protected $fillable = [
        'oil_product_id', 'distributor_profile_id', 'departure_location', 'destination',
        'departure_date', 'arrival_date', 'distance_km', 'transport_type', 'status', 'co2_estimate',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'distance_km' => 'decimal:2',
            'transport_type' => TransportType::class,
            'status' => ShipmentStatus::class,
            'co2_estimate' => 'decimal:2',
        ];
    }

    public function oilProduct(): BelongsTo
    {
        return $this->belongsTo(OilProduct::class);
    }

    public function distributorProfile(): BelongsTo
    {
        return $this->belongsTo(DistributorProfile::class);
    }

    public function scopeByStatus(Builder $query, ShipmentStatus|string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDepartureDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from !== null, fn (Builder $query) => $query->whereDate('departure_date', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('departure_date', '<=', $to));
    }

    public function scopeForDistributorUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('distributorProfile', fn (Builder $profile) => $profile->where('user_id', $userId));
    }
}
