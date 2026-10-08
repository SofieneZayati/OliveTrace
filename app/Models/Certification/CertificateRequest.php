<?php

namespace App\Models\Certification;

use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    use HasFactory;

    protected $fillable = ['oil_lot_id', 'producer_user_id', 'requested_at', 'status', 'note'];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function oilLot()
    {
        return $this->belongsTo(OilLot::class);
    }

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_user_id');
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
