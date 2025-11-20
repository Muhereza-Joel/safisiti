<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class BackfillCollectionBatchUuids extends Command
{
    // Command name
    protected $signature = 'backfill:collection-batch-uuids';

    // Description
    protected $description = 'Backfill vehicle, organisation, dumpsite ID/UUIDs (both directions) and clear cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CollectionBatch ID/UUID sync...');

        // -------------------------------------------------------------
        // PASS 1 — ID → UUID  (Fill missing UUIDs based on IDs)
        // -------------------------------------------------------------
        $this->info('Pass 1/2: Filling NULL vehicle_uuids from vehicle_ids...');
        DB::table('collection_batches AS cb')
            ->join('vehicles AS v', 'cb.vehicle_id', '=', 'v.id')
            ->whereNull('cb.vehicle_uuid')
            ->where('cb.vehicle_id', '>', 0)
            ->update(['cb.vehicle_uuid' => DB::raw('v.uuid')]);


        // -------------------------------------------------------------
        // PASS 2 — UUID → ID  (Fix 0 or NULL IDs using UUIDs)
        // -------------------------------------------------------------
        $this->info('Pass 2/2: Filling missing vehicle_ids from vehicle_uuids...');
        DB::table('collection_batches AS cb')
            ->join('vehicles AS v', 'cb.vehicle_uuid', '=', 'v.uuid')
            ->where(function ($q) {
                $q->whereNull('cb.vehicle_id')->orWhere('cb.vehicle_id', '=', 0);
            })
            ->whereNotNull('cb.vehicle_uuid')
            ->update(['cb.vehicle_id' => DB::raw('v.id')]);


        $this->info('✅ CollectionBatch ID/UUID sync completed!');

        // -------------------------------------------------------------
        // CLEAR CACHE
        // -------------------------------------------------------------
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');
        $this->info('✅ Application cache cleared!');

        return 0;
    }
}
