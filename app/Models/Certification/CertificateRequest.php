<?php

namespace App\Models\Certification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CertificateRequest extends Model
{
    use HasFactory;

    protected $fillable = ['oil_lot_id', 'producer_user_id', 'requested_at', 'status', 'note'];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function oilLot()
    {
        return $this->belongsTo(\App\Models\Production\OilLot::class);
    }

    public function producer()
    {
        return $this->belongsTo(\App\Models\User::class, 'producer_user_id');
    }

    public function labAnalysis()
    {
        return $this->hasOne(LabAnalysis::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }
}
