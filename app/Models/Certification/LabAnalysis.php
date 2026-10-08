<?php

namespace App\Models\Certification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabAnalysis extends Model
{
    use HasFactory;

    protected $fillable = ['certificate_request_id', 'lab_user_id', 'analysis_date', 'acidity', 'peroxide_value', 'result', 'notes'];

    protected $casts = [
        'analysis_date' => 'datetime',
        'result' => 'boolean',
    ];

    public function certificateRequest()
    {
        return $this->belongsTo(CertificateRequest::class);
    }

    public function labUser()
    {
        return $this->belongsTo(User::class, 'lab_user_id');
    }
}
