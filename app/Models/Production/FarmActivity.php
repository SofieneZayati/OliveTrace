<?php

namespace App\Models\Production;

use App\Enums\FarmActivityStatus;
use App\Enums\FarmActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmActivity extends Model
{
    protected $attributes = ['status' => 'planned'];

    protected $fillable = ['title', 'type', 'planned_on', 'notes'];

    protected function casts(): array
    {
        return [
            'type' => FarmActivityType::class,
            'status' => FarmActivityStatus::class,
            'planned_on' => 'immutable_date',
            'completed_on' => 'immutable_date',
            'water_m3' => 'decimal:2',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === FarmActivityStatus::Planned && $this->planned_on->lt(today());
    }
}
