<?php

namespace Database\Seeders;

use App\Models\{Mill, User};
use Illuminate\Database\Seeder;

class MillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $miller = User::where('email', 'miller@test.com')->first();

        if ($miller) {
            // Huilerie de la démo, liée au compte miller de développement.
            $demo = Mill::withTrashed()->updateOrCreate(
                ['user_id' => $miller->id],
                [
                    'name' => 'Huilerie Zitouna Sfax',
                    'region' => 'Sfax',
                    'extraction_type' => 'continuous_two_phase',
                    'capacity' => 2000,
                    'contact' => '+216 74 000 000',
                ]
            );

            if ($demo->trashed()) {
                $demo->restore();
            }
        }

        if (Mill::count() < 4) {
            Mill::factory()->count(3)->create();
        }
    }
}
