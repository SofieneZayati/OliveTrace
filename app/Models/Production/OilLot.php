<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OilLot extends Model
{
    use HasFactory;

    protected $fillable = ['producer_user_id', 'lot_number'];

    public function producer()
    {
        return $this->belongsTo(\App\Models\User::class, 'producer_user_id');
    }

    public function certificateRequests()
    {
        return $this->hasMany(\App\Models\Certification\CertificateRequest::class);
    }
}
