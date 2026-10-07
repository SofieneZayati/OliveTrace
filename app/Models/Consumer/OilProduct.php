<?php

namespace App\Models\Consumer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Read-only integration contract with Hana's Product & Distribution module.
// Module 5 never creates, updates or deletes oil products: it only reads the
// stable product identity (id / slug / QR token) to anchor feedback,
// complaints and the public trace page. The table is owned by Hana.
class OilProduct extends Model
{
    protected $table = 'oil_products';

    protected $guarded = [];

    public static function boot(): void
    {
        parent::boot();

        // No writes from Module 5: products belong to Hana's module.
        static::creating(fn () => false);
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'oil_product_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'oil_product_id');
    }
}
