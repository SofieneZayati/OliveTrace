<?php

namespace App\Models\Distribution;

use App\Contracts\OilLotLookup;
use App\Data\OilLotSummary;
use App\Enums\OilProductPublicStatus;
use App\Enums\ShipmentStatus;
use App\Models\User;
use Database\Factories\Distribution\OilProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class OilProduct extends Model
{
    /** @use HasFactory<OilProductFactory> */
    use HasFactory;

    protected $table = 'oil_products';

    protected $attributes = [
        'public_status' => 'visible',
    ];

    protected $fillable = [
        'oil_lot_id', 'name', 'brand', 'bottle_volume_ml', 'packaging_date', 'image', 'slug',
    ];

    protected function casts(): array
    {
        return [
            'oil_lot_id' => 'integer',
            'bottle_volume_ml' => 'integer',
            'packaging_date' => 'date',
            'public_status' => OilProductPublicStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function oilLot(): ?OilLotSummary
    {
        return app(OilLotLookup::class)->find((int) $this->oil_lot_id);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->public_status === OilProductPublicStatus::Visible && $this->archived_at === null;
    }

    public function totalCo2Kg(): float
    {
        return (float) $this->shipments()
            ->where('status', '!=', ShipmentStatus::Cancelled->value)
            ->whereNotNull('co2_estimate')
            ->sum('co2_estimate');
    }

    public function archive(): bool
    {
        $this->archived_at ??= Carbon::now();
        $this->public_status = OilProductPublicStatus::Hidden;

        return $this->save();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->active()->where('public_status', OilProductPublicStatus::Visible);
    }

    public function scopeByStatus(Builder $query, OilProductPublicStatus|string $status): Builder
    {
        return $query->where('public_status', $status);
    }

    public function scopePackagingDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from !== null, fn (Builder $query) => $query->whereDate('packaging_date', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('packaging_date', '<=', $to));
    }

    public function scopeOwnedBy(Builder $query, User|int $owner): Builder
    {
        return $query->where('created_by_user_id', $owner instanceof User ? $owner->id : $owner);
    }
}
