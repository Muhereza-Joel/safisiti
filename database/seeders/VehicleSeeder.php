<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Truck', 'Compactor', 'Skip Loader', 'Tanker'];
        $models = ['Isuzu NQR', 'Mercedes Atego', 'Tata LPT', 'Hino 500'];
        $descriptions = [
            'Used for general waste collection.',
            'Dedicated for organic waste.',
            'Recyclables collection unit.',
            'Heavy duty vehicle for industrial waste.'
        ];

        foreach ([2, 3] as $organisationId) {
            for ($i = 1; $i <= 10; $i++) {
                Vehicle::create([
                    'uuid' => Str::uuid()->toString(),
                    'registration_number' => 'U' . chr(64 + $organisationId) . sprintf('%03dT', $i),
                    'model' => $models[array_rand($models)],
                    'capacity' => rand(3000, 8000), // in kilograms
                    'type' => $types[array_rand($types)],
                    'description' => $descriptions[array_rand($descriptions)],
                    'user_id' => 4,
                    'organisation_id' => $organisationId,
                ]);
            }
        }

        $this->command->info('20 vehicles seeded for organisations 2 (Fort Portal) and 3 (Jinja).');
    }
}
