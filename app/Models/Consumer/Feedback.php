<?php

namespace App\Models\Consumer;

use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
use App\Enums\Consumer\FeedbackStatus;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Database\Factories\Consumer\FeedbackFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Feedback extends Model
{
    protected static function booted(): void
    {
        static::updating(function (Feedback $feedback): void {
            if ($feedback->isDirty(['rating', 'comment'])) {
                $feedback->last_edited_at = now();
            }
        });
        static::updated(function (Feedback $feedback): void {
            if ($feedback->wasChanged(['rating', 'comment'])) {
                FeedbackEdit::create([
                    'feedback_id' => $feedback->id,
                    'old_rating' => $feedback->getOriginal('rating'), 'old_comment' => $feedback->getOriginal('comment'),
                    'new_rating' => $feedback->rating, 'new_comment' => $feedback->comment,
                    'edited_by' => auth()->id() ?? $feedback->consumer_user_id,
                ]);
            }
        });
    }

    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $attributes = ['status' => 'visible', 'verified_purchase' => false];

    protected $fillable = [
        'oil_product_id', 'consumer_user_id', 'rating', 'comment', 'status',
        'ai_category', 'ai_sentiment', 'ai_priority',
        'verified_purchase', 'last_edited_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => FeedbackStatus::class,
            'ai_category' => FeedbackCategory::class,
            'ai_sentiment' => FeedbackSentiment::class,
            'ai_priority' => FeedbackPriority::class,
            'verified_purchase' => 'boolean',
            'last_edited_at' => 'datetime',
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

    public function edits(): HasMany
    {
        return $this->hasMany(FeedbackEdit::class);
    }

    public function verifiedPurchase(): HasOne
    {
        return $this->hasOne(VerifiedPurchase::class, 'consumer_user_id', 'consumer_user_id')
            ->where('oil_product_id', $this->oil_product_id);
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

    public function hasVerifiedPurchase(): bool
    {
        return VerifiedPurchase::where('consumer_user_id', $this->consumer_user_id)
            ->where('oil_product_id', $this->oil_product_id)->whereNotNull('approved_at')->exists();
    }
}
