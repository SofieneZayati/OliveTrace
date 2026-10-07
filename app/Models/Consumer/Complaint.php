<?php

namespace App\Models\Consumer;

use App\Enums\Consumer\ComplaintStatus;
use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
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
        'oil_product_id', 'consumer_user_id', 'subject', 'description', 'status',
        'admin_response', 'ai_category', 'ai_sentiment', 'ai_priority', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'ai_category' => FeedbackCategory::class,
            'ai_sentiment' => FeedbackSentiment::class,
            'ai_priority' => FeedbackPriority::class,
            'resolved_at' => 'datetime',
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

    public function isOwnedBy(User $user): bool
    {
        return $this->consumer_user_id === $user->id;
    }
}
