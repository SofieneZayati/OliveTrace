<?php

namespace App\Models\Consumer;

use App\Models\Distribution\OilProduct;
use App\Models\User;
use Database\Factories\Consumer\ConsumerNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerNotification extends Model
{
    /** @use HasFactory<ConsumerNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'consumer_user_id', 'type', 'title', 'message',
        'related_feedback_id', 'related_complaint_id', 'related_oil_product_id', 'read',
    ];

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_user_id');
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'related_feedback_id');
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'related_complaint_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OilProduct::class, 'related_oil_product_id');
    }

    public function markAsRead(): void
    {
        $this->update(['read' => true]);
    }

    public static function unreadCount(int $userId): int
    {
        return static::where('consumer_user_id', $userId)->where('read', false)->count();
    }
}
