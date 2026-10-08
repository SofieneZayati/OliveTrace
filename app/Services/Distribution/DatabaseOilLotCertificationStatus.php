<?php

namespace App\Services\Distribution;

use App\Contracts\BatchOilLotCertificationStatusProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseOilLotCertificationStatus implements BatchOilLotCertificationStatusProvider
{
    public function statusFor(int $oilLotId): string
    {
        return $this->statusesFor([$oilLotId])[$oilLotId] ?? 'not available';
    }

    public function statusesFor(array $oilLotIds): array
    {
        if ($oilLotIds === []) {
            return [];
        }
        $rows = DB::table('certificate_requests as requests')
            ->leftJoin('certificates as certificates', function ($join) {
                $join->on('certificates.certificate_request_id', '=', 'requests.id')
                    ->whereRaw('certificates.id = (select max(c.id) from certificates c where c.certificate_request_id = requests.id)');
            })
            ->whereIn('requests.id', DB::table('certificate_requests')->selectRaw('MAX(id)')->whereIn('oil_lot_id', $oilLotIds)->groupBy('oil_lot_id'))
            ->get(['requests.oil_lot_id', 'requests.status as request_status', 'certificates.status', 'certificates.expiry_date', 'certificates.issue_date']);
        $statuses = array_fill_keys($oilLotIds, 'not available');
        foreach ($rows as $row) {
            $status = $row->request_status === 'approved' ? 'pending' : $row->request_status;
            if ($row->status !== null) {
                $status = Carbon::parse($row->expiry_date)->toDateString() < now()->toDateString() ? 'expired'
                    : ($row->request_status === 'approved' && Carbon::parse($row->issue_date)->toDateString() <= now()->toDateString()
                        && in_array($row->status, ['active', 'valid', 'verified', 'certified'], true) ? 'verified'
                        : (in_array($row->status, ['active', 'valid', 'verified', 'certified'], true) ? $status : $row->status));
            }
            $statuses[(int) $row->oil_lot_id] = $status;
        }

        return $statuses;
    }
}
