<?php

namespace Database\Seeders;

use App\Models\CollectionRoute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CollectionRouteSeeder extends Seeder
{
    public function run(): void
    {
        $frequencies = ['daily', 'weekly', 'bi-weekly', 'monthly', 'custom'];
        $statuses = ['active', 'inactive', 'pending'];
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ([2, 3] as $orgId) {
            for ($i = 1; $i <= 100; $i++) {
                CollectionRoute::create([
                    'name' => 'Route ' . Str::upper(Str::random(3)) . " ($orgId)",
                    'description' => fake()->sentence(),
                    'frequency' => Arr::random($frequencies),
                    'collection_days' => Arr::random($weekdays, rand(1, 4)),
                    'start_time' => '08:00',
                    'end_time' => '18:00',
                    'status' => Arr::random($statuses),
                    'notes' => fake()->optional()->sentence(),
                    'organisation_id' => $orgId,
                ]);
            }
        }
    }
}
