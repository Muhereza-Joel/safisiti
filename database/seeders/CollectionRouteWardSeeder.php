<?php

namespace Database\Seeders;

use App\Models\CollectionRoute;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class CollectionRouteWardSeeder extends Seeder
{
    public function run(): void
    {
        $routes = CollectionRoute::all();

        foreach ($routes as $route) {
            // Get wards for the same organisation
            $wards = Ward::where('organisation_id', $route->organisation_id)
                ->inRandomOrder()
                ->take(rand(2, 5))
                ->get();

            $order = 1;
            foreach ($wards as $ward) {
                $route->wards()->attach($ward->id, [
                    'uuid' => Uuid::uuid4()->toString(),
                    'collection_route_uuid' => $route->uuid,
                    'ward_uuid' => $ward->uuid,
                    'collection_order' => $order++,
                ]);
            }
        }
    }
}
