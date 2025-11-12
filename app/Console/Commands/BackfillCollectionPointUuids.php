<?php

namespace App\Console\Commands;

// No models are needed here anymore since we use DB::table()
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Make sure this is imported

class BackfillCollectionPointUuids extends Command
{
    // The name and signature of the console command
    protected $signature = 'backfill:collection-point-uuids';

    // The console command description
    protected $description = 'Backfill ward, cell, and organisation IDs/UUIDs (in both directions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CollectionPoint ID/UUID sync...');

        // --- PASS 1: Sync ID -> UUID (Fixes NULL UUIDs) ---
        $this->info('Pass 1/2: Backfilling NULL ward_uuids from ward_ids...');
        DB::table('collection_points AS cp')
            ->join('wards AS w', 'cp.ward_id', '=', 'w.id')
            ->whereNull('cp.ward_uuid')
            ->where('cp.ward_id', '>', 0) // Skips 0 and NULL
            ->update(['cp.ward_uuid' => DB::raw('w.uuid')]);

        $this->info('Pass 1/2: Backfilling NULL cell_uuids from cell_ids...');
        DB::table('collection_points AS cp')
            ->join('cells AS c', 'cp.cell_id', '=', 'c.id')
            ->whereNull('cp.cell_uuid')
            ->where('cp.cell_id', '>', 0) // Skips 0 and NULL
            ->update(['cp.cell_uuid' => DB::raw('c.uuid')]);

        $this->info('Pass 1/2: Backfilling NULL organisation_uuids from organisation_ids...');
        DB::table('collection_points AS cp')
            ->join('organisations AS o', 'cp.organisation_id', '=', 'o.id')
            ->whereNull('cp.organisation_uuid')
            ->where('cp.organisation_id', '>', 0) // Skips 0 and NULL
            ->update(['cp.organisation_uuid' => DB::raw('o.uuid')]);


        // --- PASS 2: Sync UUID -> ID (Fixes 0 or NULL IDs) ---
        // This is the new logic you requested.
        $this->info('Pass 2/2: Backfilling 0 or NULL ward_ids from ward_uuids...');
        DB::table('collection_points AS cp')
            ->join('wards AS w', 'cp.ward_uuid', '=', 'w.uuid') // Join on UUID
            ->where(function ($query) {
                $query->whereNull('cp.ward_id')
                    ->orWhere('cp.ward_id', '=', 0);
            })
            ->whereNotNull('cp.ward_uuid')
            ->update(['cp.ward_id' => DB::raw('w.id')]); // Update the ID

        $this->info('Pass 2/2: Backfilling 0 or NULL cell_ids from cell_uuids...');
        DB::table('collection_points AS cp')
            ->join('cells AS c', 'cp.cell_uuid', '=', 'c.uuid') // Join on UUID
            ->where(function ($query) {
                $query->whereNull('cp.cell_id')
                    ->orWhere('cp.cell_id', '=', 0);
            })
            ->whereNotNull('cp.cell_uuid')
            ->update(['cp.cell_id' => DB::raw('c.id')]); // Update the ID

        $this->info('Pass 2/2: Backfilling 0 or NULL organisation_ids from organisation_uuids...');
        DB::table('collection_points AS cp')
            ->join('organisations AS o', 'cp.organisation_uuid', '=', 'o.uuid') // Join on UUID
            ->where(function ($query) {
                $query->whereNull('cp.organisation_id')
                    ->orWhere('cp.organisation_id', '=', 0);
            })
            ->whereNotNull('cp.organisation_uuid')
            ->update(['cp.organisation_id' => DB::raw('o.id')]); // Update the ID

        $this->info('✅ CollectionPoint ID/UUID sync complete!');
    }
}
