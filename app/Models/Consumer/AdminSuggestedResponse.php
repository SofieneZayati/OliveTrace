<?php

namespace App\Models\Consumer;

use App\Models\User;
use Database\Factories\Consumer\AdminSuggestedResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSuggestedResponse extends Model
{
    /** @use HasFactory<AdminSuggestedResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_id', 'suggested_response', 'tone', 'confidence',
        'used', 'used_by', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function markAsUsed(User $admin): void
    {
        $this->update([
            'used' => true,
            'used_by' => $admin->id,
            'used_at' => now(),
        ]);
    }
}
