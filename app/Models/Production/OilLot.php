<?php

namespace App\Models\Production;

use App\Enums\OilQuality;
use App\Models\Certification\CertificateRequest;
use App\Models\User;
use Database\Factories\Production\OilLotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class OilLot extends Model
{
    /** @use HasFactory<OilLotFactory> */
    use HasFactory;

    protected $fillable = [
        'mill_request_id', 'producer_user_id', 'lot_number', 'liters', 'quality_grade', 'production_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'quality_grade' => OilQuality::class,
            'production_date' => 'date',
        ];
    }

    /** The milling request this lot was produced from (milling module). */
    public function millRequest(): BelongsTo
    {
        return $this->belongsTo(MillRequest::class);
    }

    /** The harvest the olives came from (milling module). */
    public function harvest(): HasOneThrough
    {
        return $this->hasOneThrough(Harvest::class, MillRequest::class, 'id', 'id', 'mill_request_id', 'harvest_id');
    }

    /** The producer who owns the lot (certification module). */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_user_id');
    }

    public function certificateRequests(): HasMany
    {
        return $this->hasMany(CertificateRequest::class);
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->producer_user_id === $userId;
    }
}
