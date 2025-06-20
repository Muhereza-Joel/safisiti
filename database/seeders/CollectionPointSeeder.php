<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CollectionPoint;
use App\Models\Cell;
use Illuminate\Support\Str;

class CollectionPointSeeder extends Seeder
{
    public function run(): void
    {
        // Define allowed options
        $categories = [
            'household',
            'market',
            'school',
            'hospital',
            'clinic',
            'restaurant',
            'hotel',
            'office',
            'shop',
            'supermarket',
            'other'
        ];

        $structureTypes = [
            'permanent',
            'semi-permanent',
            'temporary'
        ];

        $wasteTypes = [
            'domestic',
            'commercial',
            'organic',
            'recyclable',
            'hazardous',
            'mixed'
        ];

        $collectionFrequencies = [
            'daily',
            'weekly',
            'biweekly',
            'monthly'
        ];

        $binTypes = [
            'plastic',
            'metal',
            'concrete',
            'other'
        ];

        // Fetch all cells with related ward for fallback organisation_id
        $cells = Cell::with('ward')->get();

        foreach ($cells as $cell) {
            for ($i = 1; $i <= 5; $i++) {
                CollectionPoint::create([
                    'name' => "Collection Point {$i} - {$cell->name}",
                    'category' => $categories[array_rand($categories)],
                    'head_name' => fake()->name(),
                    'phone' => fake()->phoneNumber(),
                    'email' => fake()->unique()->safeEmail(),
                    'ward_id' => $cell->ward_id,
                    'cell_id' => $cell->id,
                    'address' => fake()->address(),
                    'latitude' => fake()->latitude(-1.5, 1.5),
                    'longitude' => fake()->longitude(29.5, 34.0),
                    'structure_type' => $structureTypes[array_rand($structureTypes)],
                    'household_size' => rand(3, 15),
                    'waste_type' => $wasteTypes[array_rand($wasteTypes)],
                    'collection_frequency' => $collectionFrequencies[array_rand($collectionFrequencies)],
                    'bin_count' => rand(1, 5),
                    'bin_type' => $binTypes[array_rand($binTypes)],
                    'last_collection_date' => now()->subDays(rand(0, 14)),
                    'notes' => fake()->sentence(),
                    'organisation_id' => $cell->organisation_id ?? $cell->ward->organisation_id,
                ]);
            }
        }
    }
}
