<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ward;
use App\Models\Cell;

class CellSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all wards
        $wards = Ward::all();

        foreach ($wards as $ward) {
            for ($i = 1; $i <= 40; $i++) {
                Cell::create([
                    'name' => "Cell {$i} of {$ward->name}",
                    'ward_id' => $ward->id,
                    // organisation_id will be auto-set if user is authenticated,
                    // but since seeding is not via web auth, we explicitly set it
                    'organisation_id' => $ward->organisation_id,
                ]);
            }
        }
    }
}
