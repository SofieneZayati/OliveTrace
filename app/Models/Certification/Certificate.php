<?php

namespace App\Models\Certification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = ['certificate_request_id', 'certificate_number', 'type', 'issue_date', 'expiry_date', 'pdf_url', 'status', 'metadata'];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    public function certificateRequest()
    {
        return $this->belongsTo(CertificateRequest::class);
    }

    public function isCurrentlyValid(): bool
    {
        return $this->certificateRequest?->status === 'approved'
            && in_array($this->status, ['active', 'valid', 'verified', 'certified'], true)
            && $this->issue_date !== null && $this->issue_date->lte(today())
            && $this->expiry_date !== null && $this->expiry_date->gte(today());
    }
}
