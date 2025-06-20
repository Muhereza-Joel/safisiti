<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ward;

class WardSeeder extends Seeder
{
    public function run(): void
    {

        // Wards for Fort Portal (organisation_id = 3)
        for ($i = 1; $i <= 100; $i++) {
            Ward::create([
                'name' => "Fort Portal Ward {$i}",
                'code' => "FP-WD-{$i}",
                'population' => rand(4000, 12000),
                'area_sq_km' => round(mt_rand(100, 400) / 100, 2), // 1.0 - 4.0 sq km
                'description' => "Ward number {$i} in Fort Portal.",
                'latitude' => 0.67 + mt_rand(-100, 100) / 10000,
                'longitude' => 30.27 + mt_rand(-100, 100) / 10000,
                'organisation_id' => 2,
            ]);
        }

        // Wards for Jinja (organisation_id = 2)
        for ($i = 1; $i <= 100; $i++) {
            Ward::create([
                'name' => "Jinja Ward {$i}",
                'code' => "JN-WD-{$i}",
                'population' => rand(5000, 15000),
                'area_sq_km' => round(mt_rand(150, 500) / 100, 2), // 1.5 - 5.0 sq km
                'description' => "Ward number {$i} in Jinja.",
                'latitude' => 0.44 + mt_rand(-100, 100) / 10000, // nearby variation
                'longitude' => 33.20 + mt_rand(-100, 100) / 10000,
                'organisation_id' => 3,
            ]);
        }
    }
}
