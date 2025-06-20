<?php

namespace Database\Seeders;

use App\Models\CollectionRoute;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class CollectionRouteWardSeeder extends Seeder
{
    public function run(): void
    {
        $routes = CollectionRoute::all();

        foreach ($routes as $route) {
            // Get wards for the same organisation
            $wards = Ward::where('organisation_id', $route->organisation_id)->inRandomOrder()->take(rand(2, 5))->get();

            $order = 1;
            foreach ($wards as $ward) {
                // Attach with collection_order
                $route->wards()->attach($ward->id, [
                    'collection_order' => $order++,
                ]);
            }
        }
    }
}
