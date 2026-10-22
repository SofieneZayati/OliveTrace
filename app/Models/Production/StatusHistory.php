<?php

namespace App\Models\Production;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
