<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Certification\Certificate;
use App\Models\Certification\CertificateRequest;
use App\Models\Certification\LabAnalysis;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Certification demo data may only be seeded in local or testing environments.');
        }

        $producer = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        $lab      = User::where('email', 'lab@test.com')->where('role', Role::Laboratory)->first();

        if (! $producer || ! $lab) {
            throw new LogicException('Seed DevelopmentUserSeeder first to create producer and laboratory accounts.');
        }

        $lot1 = OilLot::where('lot_number', 'LOT-2026-001')->first();
        $lot2 = OilLot::where('lot_number', 'LOT-2026-002')->first();

        if (! $lot1 || ! $lot2) {
            throw new LogicException('Seed OilLotSeeder first to create the demo oil lots.');
        }

        // ── LOT-2026-001: fully certified (request → lab analysis → certificate) ──
        $req1 = CertificateRequest::firstOrCreate(
            ['oil_lot_id' => $lot1->id],
            [
                'producer_user_id' => $producer->id,
                'status'           => 'approved',
                'note'             => 'First press of the Parcel En Nour harvest. Please certify as EVOO.',
                'requested_at'     => '2026-09-29 08:00:00',
            ]
        );

        $analysis1 = LabAnalysis::firstOrCreate(
            ['certificate_request_id' => $req1->id],
            [
                'lab_user_id'   => $lab->id,
                'analysis_date' => '2026-10-01 10:30:00',
                'acidity'       => '0.22',
                'peroxide_value'=> '6.40',
                'result'        => true,
                'notes'         => 'Acidity well below 0.8% threshold. Polyphenol profile excellent. Certified Extra Virgin.',
            ]
        );

        Certificate::firstOrCreate(
            ['certificate_request_id' => $req1->id],
            [
                'certificate_number' => 'CERT-OT-2026-001',
                'type'               => 'extra_virgin',
                'issue_date'         => '2026-10-02',
                'expiry_date'        => '2027-10-02',
                'pdf_url'            => null,
                'status'             => 'valid',
                'metadata'           => [
                    'lot_number'  => 'LOT-2026-001',
                    'acidity'     => '0.22%',
                    'variety'     => 'Chemlali',
                    'region'      => 'Sfax',
                ],
            ]
        );

        // ── LOT-2026-002: lab analysis done, certificate pending admin approval ──
        $req2 = CertificateRequest::firstOrCreate(
            ['oil_lot_id' => $lot2->id],
            [
                'producer_user_id' => $producer->id,
                'status'           => 'pending',
                'note'             => 'Premium early hand-picked batch. Very low acidity expected.',
                'requested_at'     => '2026-10-06 09:00:00',
            ]
        );

        LabAnalysis::firstOrCreate(
            ['certificate_request_id' => $req2->id],
            [
                'lab_user_id'    => $lab->id,
                'analysis_date'  => '2026-10-07 11:00:00',
                'acidity'        => '0.18',
                'peroxide_value' => '5.10',
                'result'         => true,
                'notes'          => 'Outstanding quality. Acidity 0.18%, well within premium EVOO standards.',
            ]
        );
        // No Certificate yet — waiting for admin to approve req2
    }
}
