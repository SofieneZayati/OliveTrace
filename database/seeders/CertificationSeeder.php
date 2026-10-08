<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        // Create Producer User
        $producer = \App\Models\User::firstOrCreate(
            ['email' => 'producer@olivetrace.com'],
            [
                'name' => 'Oussema Producer',
                'password' => bcrypt('password'),
                'role' => \App\Enums\Role::Producer,
                'is_active' => true,
            ]
        );

        // Create Lab User
        $lab = \App\Models\User::firstOrCreate(
            ['email' => 'lab@olivetrace.com'],
            [
                'name' => 'OliveTrace Lab',
                'password' => bcrypt('password'),
                'role' => \App\Enums\Role::Laboratory, // Assuming this role exists
                'is_active' => true,
            ]
        );

        // Create a stub Oil Lot
        $lot = \App\Models\Production\OilLot::firstOrCreate(
            ['lot_number' => 'LOT-2026-001'],
            ['producer_user_id' => $producer->id]
        );
        
        \App\Models\Production\OilLot::firstOrCreate(
            ['lot_number' => 'LOT-2026-002'],
            ['producer_user_id' => $producer->id]
        );

        // Create a pending request for LOT-001
        \App\Models\Certification\CertificateRequest::firstOrCreate(
            ['oil_lot_id' => $lot->id],
            [
                'producer_user_id' => $producer->id,
                'status' => 'pending',
                'note' => 'Please verify my latest harvest.',
            ]
        );
    }
}
