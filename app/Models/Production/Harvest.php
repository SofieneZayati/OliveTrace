<?php

namespace App\Models\Production;

use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use Database\Factories\Production\HarvestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harvest extends Model
{
    /** @use HasFactory<HarvestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id', 'harvest_date', 'expected_end_date', 'method', 'quantity_kg', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'expected_end_date' => 'date',
            'method' => HarvestMethod::class,
            'quantity_kg' => 'decimal:2',
            'status' => HarvestStatus::class,
        ];
    }

    public function getQuantityKgAttribute($value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function millRequests(): HasMany
    {
        return $this->hasMany(MillRequest::class);
    }

    /** Latest request that is not a producer cancellation, used for the milling status. */
    public function millingRequest(): HasOne
    {
        return $this->hasOne(MillRequest::class)->where('status', '!=', MillRequestStatus::Cancelled)->latestOfMany();
    }

    public function requestedQuantity(): float
    {
        return (float) $this->millRequests()->whereIn('status', MillRequestStatus::ACTIVE)->sum('quantity_kg');
    }

    public function remainingQuantity(): ?float
    {
        if ($this->quantity_kg === null) {
            return null;
        }
        return max(0, (float) $this->quantity_kg - $this->requestedQuantity());
    }

    public function millingStatus(): ?MillRequestStatus
    {
        return $this->millingRequest?->status;
    }
}
