<?php

namespace App\Models;

use App\Models\Production\MillRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mill extends Model
{
    /** @use HasFactory<\Database\Factories\MillFactory> */
    use HasFactory, SoftDeletes;

    public const EXTRACTION_TYPES = [
        'traditional_press',
        'continuous_two_phase',
        'continuous_three_phase',
    ];

    protected $fillable = [
        'user_id', 'name', 'region', 'extraction_type', 'capacity', 'contact',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function millRequests(): HasMany
    {
        return $this->hasMany(MillRequest::class);
    }
}
