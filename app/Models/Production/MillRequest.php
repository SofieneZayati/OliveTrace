<?php

namespace App\Models\Production;

use App\Enums\MillRequestStatus;
use App\Models\Mill;
use Database\Factories\Production\MillRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MillRequest extends Model
{
    /** @use HasFactory<MillRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'harvest_id', 'mill_id', 'external_mill_name', 'requested_date', 'appointment_date',
        'quantity_kg', 'status', 'message', 'response_message',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'appointment_date' => 'date',
            'quantity_kg' => 'decimal:2',
            'status' => MillRequestStatus::class,
        ];
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    public function mill(): BelongsTo
    {
        return $this->belongsTo(Mill::class)->withTrashed();
    }

    public function targetLabel(): string
    {
        return $this->mill?->name ?? ($this->external_mill_name ?: 'External mill');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->harvest?->farm?->producerProfile?->user_id === $userId;
    }

    public function oilLot(): HasOne
    {
        return $this->hasOne(OilLot::class);
    }
}
