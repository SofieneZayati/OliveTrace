<?php

namespace App\Models\Consumer;

use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Database\Factories\Consumer\VerifiedPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifiedPurchase extends Model
{
    /** @use HasFactory<VerifiedPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'consumer_user_id', 'oil_product_id', 'shipment_id',
        'proof_type', 'proof_path', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
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

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
