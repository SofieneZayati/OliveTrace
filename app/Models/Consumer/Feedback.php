<?php

namespace App\Models\Consumer;

use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
use App\Enums\Consumer\FeedbackStatus;
use App\Models\User;
use Database\Factories\Consumer\FeedbackFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $attributes = ['status' => 'visible'];

    protected $fillable = [
        'oil_product_id', 'consumer_user_id', 'rating', 'comment', 'status',
        'ai_category', 'ai_sentiment', 'ai_priority',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => FeedbackStatus::class,
            'ai_category' => FeedbackCategory::class,
            'ai_sentiment' => FeedbackSentiment::class,
            'ai_priority' => FeedbackPriority::class,
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OilProduct::class, 'oil_product_id');
    }

    /** @param Builder<Feedback> $query */
    public function scopeVisible($query)
    {
        return $query->where('status', FeedbackStatus::Visible);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->consumer_user_id === $user->id;
    }
}
