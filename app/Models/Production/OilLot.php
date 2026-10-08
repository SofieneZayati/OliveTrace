<?php

namespace App\Models\Production;

use App\Enums\OilQuality;
use Database\Factories\Production\OilLotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OilLot extends Model
{
    /** @use HasFactory<OilLotFactory> */
    use HasFactory;

    protected $fillable = [
        'mill_request_id', 'lot_number', 'liters', 'quality_grade', 'production_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'quality_grade' => OilQuality::class,
            'production_date' => 'date',
        ];
    }

    public function millRequest(): BelongsTo
    {
        return $this->belongsTo(MillRequest::class);
    }

    public function harvest(): BelongsTo
    {
        return $this->hasOneThrough(Harvest::class, MillRequest::class, 'id', 'id', 'mill_request_id', 'harvest_id');
    }
}
