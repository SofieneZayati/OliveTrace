<?php

namespace App\Models\Consumer;

use App\Enums\Consumer\ComplaintStatus;
use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Database\Factories\Consumer\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'open'];

    protected $fillable = [
        'oil_product_id', 'consumer_user_id', 'subject', 'description', 'evidence_photo', 'status',
        'admin_response', 'ai_category', 'ai_sentiment', 'ai_priority', 'ai_draft_reply', 'resolved_at',
        'sla_deadline', 'reopen_count', 'admin_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'ai_category' => FeedbackCategory::class,
            'ai_sentiment' => FeedbackSentiment::class,
            'ai_priority' => FeedbackPriority::class,
            'resolved_at' => 'datetime',
            'sla_deadline' => 'datetime',
            'reopen_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint): void {
            $complaint->sla_deadline ??= now()->addHours(48);
        });
    }

    public function isOverdue(): bool
    {
        return $this->sla_deadline !== null && $this->sla_deadline->isPast() && ! $this->status->isClosed();
    }

    public function isSlaBreached(): bool
    {
        return $this->isOverdue();
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OilProduct::class, 'oil_product_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->consumer_user_id === $user->id;
    }
}
