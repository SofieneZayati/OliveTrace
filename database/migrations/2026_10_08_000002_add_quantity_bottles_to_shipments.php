<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->unsignedInteger('quantity_bottles')->default(1)->after('distance_km');
        });

        $factors = config('olivetrace-distribution.co2_emission_factors', []);
        DB::table('shipments')
            ->join('oil_products', 'shipments.oil_product_id', '=', 'oil_products.id')
            ->select([
                'shipments.id',
                'shipments.distance_km',
                'shipments.transport_type',
                'shipments.quantity_bottles',
                'oil_products.bottle_volume_ml',
            ])
            ->orderBy('shipments.id')
            ->chunk(500, function ($shipments) use ($factors): void {
                foreach ($shipments as $shipment) {
                    $factor = $factors[$shipment->transport_type] ?? null;
                    if (! is_numeric($factor)) {
                        continue;
                    }

                    $massKg = ($shipment->quantity_bottles * ($shipment->bottle_volume_ml / 1000) * 0.916)
                        + ($shipment->quantity_bottles * 0.5);
                    $estimate = round((float) $shipment->distance_km * ($massKg / 1000) * (float) $factor, 2);

                    DB::table('shipments')->where('id', $shipment->id)->update(['co2_estimate' => $estimate]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropColumn('quantity_bottles');
        });
    }
};
