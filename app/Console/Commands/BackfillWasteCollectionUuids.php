<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class BackfillWasteCollectionUuids extends Command
{
    protected $signature = 'backfill:waste-collection-uuids';

    protected $description = 'Backfill ward, cell, organisation UUIDs/IDs for WasteCollection and clear cache';

    public function handle()
    {
        $this->info('Starting WasteCollection ID/UUID sync...');

        // ========= PASS 1: ID → UUID =========

        $this->info('Pass 1/2: Backfilling NULL ward_uuid from ward_id...');
        DB::table('waste_collections AS wc')
            ->join('wards AS w', 'wc.ward_id', '=', 'w.id')
            ->whereNull('wc.ward_uuid')
            ->where('wc.ward_id', '>', 0)
            ->update(['wc.ward_uuid' => DB::raw('w.uuid')]);

        $this->info('Pass 1/2: Backfilling NULL cell_uuid from cell_id...');
        DB::table('waste_collections AS wc')
            ->join('cells AS c', 'wc.cell_id', '=', 'c.id')
            ->whereNull('wc.cell_uuid')
            ->where('wc.cell_id', '>', 0)
            ->update(['wc.cell_uuid' => DB::raw('c.uuid')]);

        $this->info('Pass 1/2: Backfilling NULL organisation_uuid from organisation_id...');
        DB::table('waste_collections AS wc')
            ->join('organisations AS o', 'wc.organisation_id', '=', 'o.id')
            ->whereNull('wc.organisation_uuid')
            ->where('wc.organisation_id', '>', 0)
            ->update(['wc.organisation_uuid' => DB::raw('o.uuid')]);



        // ========= PASS 2: UUID → ID =========

        $this->info('Pass 2/2: Backfilling 0/NULL ward_id from ward_uuid...');
        DB::table('waste_collections AS wc')
            ->join('wards AS w', 'wc.ward_uuid', '=', 'w.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.ward_id')->orWhere('wc.ward_id', 0);
            })
            ->whereNotNull('wc.ward_uuid')
            ->update(['wc.ward_id' => DB::raw('w.id')]);

        $this->info('Pass 2/2: Backfilling 0/NULL cell_id from cell_uuid...');
        DB::table('waste_collections AS wc')
            ->join('cells AS c', 'wc.cell_uuid', '=', 'c.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.cell_id')->orWhere('wc.cell_id', 0);
            })
            ->whereNotNull('wc.cell_uuid')
            ->update(['wc.cell_id' => DB::raw('c.id')]);

        $this->info('Pass 2/2: Backfilling 0/NULL organisation_id from organisation_uuid...');
        DB::table('waste_collections AS wc')
            ->join('organisations AS o', 'wc.organisation_uuid', '=', 'o.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.organisation_id')->orWhere('wc.organisation_id', 0);
            })
            ->whereNotNull('wc.organisation_uuid')
            ->update(['wc.organisation_id' => DB::raw('o.id')]);

        $this->info('✅ WasteCollection ID/UUID sync complete!');

        // ========= CLEAR CACHE =========
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');
        $this->info('✅ Cache cleared successfully!');
    }
}
