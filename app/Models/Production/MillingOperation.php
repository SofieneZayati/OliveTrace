<?php

namespace App\Models\Production;

use App\Enums\Production\MillingOperationStatus;
use App\Enums\Production\OliveCondition;
use App\Models\Mill;
use App\Models\User;
use Database\Factories\Production\MillingOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MillingOperation extends Model
{
    /** @use HasFactory<MillingOperationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mill_request_id',
        'status',
        'producer_confirmed_at',
        'delivered_at',
        'received_quantity_kg',
        'olive_condition',
        'delivery_notes',
        'miller_confirmed_at',
        'discrepancy_percent',
        'discrepancy_note',
        'discrepancy_acknowledged_at',
        'started_at',
        'completed_at',
        'processed_quantity_kg',
        'output_volume_l',
        'pomace_quantity_kg',
        'extraction_rate',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => MillingOperationStatus::class,
            'olive_condition' => OliveCondition::class,
            'producer_confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'miller_confirmed_at' => 'datetime',
            'discrepancy_acknowledged_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'received_quantity_kg' => 'decimal:2',
            'discrepancy_percent' => 'decimal:2',
            'processed_quantity_kg' => 'decimal:2',
            'output_volume_l' => 'decimal:2',
            'pomace_quantity_kg' => 'decimal:2',
            'extraction_rate' => 'decimal:2',
        ];
    }

    public function millRequest(): BelongsTo
    {
        return $this->belongsTo(MillRequest::class);
    }

    public function mill(): BelongsTo
    {
        return $this->hasOneThrough(Mill::class, MillRequest::class, 'id', 'id', 'mill_request_id', 'mill_id');
    }

    public function oilLot(): HasOne
    {
        return $this->hasOne(OilLot::class, 'milling_operation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories()
    {
        return $this->morphMany(StatusHistory::class, 'trackable');
    }
}
