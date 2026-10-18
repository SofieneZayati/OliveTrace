<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmAdviceReport extends Model
{
    protected $fillable = ['provider', 'model', 'facts', 'advice'];

    protected function casts(): array
    {
        return ['facts' => 'array', 'advice' => 'array'];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(FarmActivity::class, 'advice_report_id');
    }
}
