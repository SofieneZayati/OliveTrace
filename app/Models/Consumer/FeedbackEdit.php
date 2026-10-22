<?php

namespace App\Models\Consumer;

use App\Models\User;
use Database\Factories\Consumer\FeedbackEditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackEdit extends Model
{
    /** @use HasFactory<FeedbackEditFactory> */
    use HasFactory;

    protected $table = 'feedback_edits';

    protected $fillable = [
        'feedback_id', 'old_rating', 'old_comment', 'new_rating', 'new_comment', 'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'old_rating' => 'integer',
            'new_rating' => 'integer',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
