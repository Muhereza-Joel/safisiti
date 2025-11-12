<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\CollectionPoint;
use App\Models\Organisation;
use Illuminate\Console\Command;
use App\Models\Ward;
use Illuminate\Support\Facades\DB; // <-- 1. ADDED THIS IMPORT

class BackfillCollectionPointUuids extends Command
{
    // The name and signature of the console command
    protected $signature = 'backfill:collection-point-uuids';

    // The console command description
    protected $description = 'Backfill ward_uuid, cell_uuid, and organisation_uuid for CollectionPoints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 2. REPLACED the entire 'handle' method with this optimized version

        $this->info('Starting UUID backfill...');

        $this->info('Backfilling ward_uuid...');
        // This one query updates all ward_uuids at once
        DB::table('collection_points AS cp')
            ->join('wards AS w', 'cp.ward_id', '=', 'w.id')
            ->whereNull('cp.ward_uuid')
            ->whereNotNull('cp.ward_id')
            ->update(['cp.ward_uuid' => DB::raw('w.uuid')]);

        $this->info('Backfilling cell_uuid...');
        // This one query updates all cell_uuids at once
        DB::table('collection_points AS cp')
            ->join('cells AS c', 'cp.cell_id', '=', 'c.id')
            ->whereNull('cp.cell_uuid')
            ->whereNotNull('cp.cell_id')
            ->update(['cp.cell_uuid' => DB::raw('c.uuid')]);

        $this->info('Backfilling organisation_uuid...');
        // This one query updates all organisation_uuids at once
        DB::table('collection_points AS cp')
            ->join('organisations AS o', 'cp.organisation_id', '=', 'o.id')
            ->whereNull('cp.organisation_uuid')
            ->whereNotNull('cp.organisation_id')
            ->update(['cp.organisation_uuid' => DB::raw('o.uuid')]);

        $this->info('✅ CollectionPoint UUIDs backfilled successfully!');
    }
}
