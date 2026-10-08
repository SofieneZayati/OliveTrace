<?php

namespace Database\Seeders;

use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use Illuminate\Database\Seeder;

class OilLotSeeder extends Seeder
{
    public function run(): void
    {
        $completedRequests = MillRequest::where('status', 'completed')->get();

        foreach ($completedRequests as $request) {
            if (! $request->oilLot) {
                OilLot::factory()->for($request, 'millRequest')->create();
            }
        }
    }
}
