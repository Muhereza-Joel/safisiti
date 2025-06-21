<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DumpingSite;

class DumpingSiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Fort Portal Central Dump',
                'location' => 'Fort Portal Town Yard',
                'latitude' => '0.6710',
                'longitude' => '30.2750',
                'description' => 'Handles municipal waste for Fort Portal.',
                'organisation_id' => 2,
            ],
            [
                'name' => 'Kitere Dump',
                'location' => 'Booma Hill, Fort Portal',
                'latitude' => '0.6134',
                'longitude' => '30.6450',
                'description' => 'Backup site for Fort Portal overflow.',
                'organisation_id' => 2,
            ],
            [
                'name' => 'Jinja Industrial Dump Site',
                'location' => 'Jinja Industrial Area',
                'latitude' => '0.4244',
                'longitude' => '33.2047',
                'description' => 'Main dump site for Jinja municipality.',
                'organisation_id' => 3,
            ],
            [
                'name' => 'Bugembe Compost Yard',
                'location' => 'Bugembe Town Council',
                'latitude' => '0.4712',
                'longitude' => '33.2344',
                'description' => 'Organic waste processing for Jinja.',
                'organisation_id' => 3,
            ],
        ];

        foreach ($sites as $site) {
            DumpingSite::create($site); // uuid & organisation_id auto-set if applicable
        }
    }
}
