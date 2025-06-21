<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecyclingCenter;

class RecyclingCenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            [
                'name' => 'Fort Portal Plastic Recycling Center',
                'location' => 'Kampala Road, Fort Portal',
                'latitude' => '0.6611',
                'longitude' => '30.2720',
                'description' => 'Handles plastic recycling for Fort Portal Municipality.',
                'organisation_id' => 2,
            ],
            [
                'name' => 'Fort Compost Facility',
                'location' => 'Boma Grounds, Fort Portal',
                'latitude' => '0.6733',
                'longitude' => '30.2805',
                'description' => 'Composting biodegradable waste in Fort Portal.',
                'organisation_id' => 2,
            ],
            [
                'name' => 'Jinja Plastic Recycling Plant',
                'location' => 'Walukuba Division, Jinja',
                'latitude' => '0.4305',
                'longitude' => '33.2020',
                'description' => 'Processes plastics and metals for reuse.',
                'organisation_id' => 3,
            ],
            [
                'name' => 'Jinja Organic Compost Center',
                'location' => 'Wanyange Area, Jinja',
                'latitude' => '0.4365',
                'longitude' => '33.2130',
                'description' => 'Recycles organic waste into compost.',
                'organisation_id' => 3,
            ],
        ];

        foreach ($centers as $center) {
            RecyclingCenter::create($center); // uuid is auto-assigned by the model
        }
    }
}
