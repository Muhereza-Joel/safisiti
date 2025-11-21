<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class BackfillWasteCollectionUuids extends Command
{
    protected $signature = 'backfill:waste-collection-uuids';

    protected $description = 'Backfill ID/UUID pairs for WasteCollection records and clear cache';

    public function handle()
    {
        $this->info('Starting WasteCollection ID/UUID sync...');

        // ============================================
        // PASS 1 — ID ➜ UUID (Fix missing UUIDs)
        // ============================================

        $this->info('Pass 1/2: Filling UUIDs from IDs...');

        // collection_batch_uuid
        DB::table('waste_collections AS wc')
            ->join('collection_batches AS cb', 'wc.collection_batch_id', '=', 'cb.id')
            ->whereNull('wc.collection_batch_uuid')
            ->where('wc.collection_batch_id', '>', 0)
            ->update(['wc.collection_batch_uuid' => DB::raw('cb.uuid')]);

        // collection_point_uuid
        DB::table('waste_collections AS wc')
            ->join('collection_points AS cp', 'wc.collection_point_id', '=', 'cp.id')
            ->whereNull('wc.collection_point_uuid')
            ->where('wc.collection_point_id', '>', 0)
            ->update(['wc.collection_point_uuid' => DB::raw('cp.uuid')]);

        // waste_type_uuid
        DB::table('waste_collections AS wc')
            ->join('waste_types AS wt', 'wc.waste_type_id', '=', 'wt.id')
            ->whereNull('wc.waste_type_uuid')
            ->where('wc.waste_type_id', '>', 0)
            ->update(['wc.waste_type_uuid' => DB::raw('wt.uuid')]);

        // user_uuid
        DB::table('waste_collections AS wc')
            ->join('users AS u', 'wc.user_id', '=', 'u.id')
            ->whereNull('wc.user_uuid')
            ->where('wc.user_id', '>', 0)
            ->update(['wc.user_uuid' => DB::raw('u.uuid')]);

        // organisation_uuid
        DB::table('waste_collections AS wc')
            ->join('organisations AS o', 'wc.organisation_id', '=', 'o.id')
            ->whereNull('wc.organisation_uuid')
            ->where('wc.organisation_id', '>', 0)
            ->update(['wc.organisation_uuid' => DB::raw('o.uuid')]);



        // ============================================
        // PASS 2 — UUID ➜ ID (Fix missing IDs)
        // ============================================

        $this->info('Pass 2/2: Filling IDs from UUIDs...');

        // collection_batch_id
        DB::table('waste_collections AS wc')
            ->join('collection_batches AS cb', 'wc.collection_batch_uuid', '=', 'cb.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.collection_batch_id')->orWhere('wc.collection_batch_id', 0);
            })
            ->whereNotNull('wc.collection_batch_uuid')
            ->update(['wc.collection_batch_id' => DB::raw('cb.id')]);

        // collection_point_id
        DB::table('waste_collections AS wc')
            ->join('collection_points AS cp', 'wc.collection_point_uuid', '=', 'cp.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.collection_point_id')->orWhere('wc.collection_point_id', 0);
            })
            ->whereNotNull('wc.collection_point_uuid')
            ->update(['wc.collection_point_id' => DB::raw('cp.id')]);

        // waste_type_id
        DB::table('waste_collections AS wc')
            ->join('waste_types AS wt', 'wc.waste_type_uuid', '=', 'wt.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.waste_type_id')->orWhere('wc.waste_type_id', 0);
            })
            ->whereNotNull('wc.waste_type_uuid')
            ->update(['wc.waste_type_id' => DB::raw('wt.id')]);

        // user_id
        DB::table('waste_collections AS wc')
            ->join('users AS u', 'wc.user_uuid', '=', 'u.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.user_id')->orWhere('wc.user_id', 0);
            })
            ->whereNotNull('wc.user_uuid')
            ->update(['wc.user_id' => DB::raw('u.id')]);

        // organisation_id
        DB::table('waste_collections AS wc')
            ->join('organisations AS o', 'wc.organisation_uuid', '=', 'o.uuid')
            ->where(function ($q) {
                $q->whereNull('wc.organisation_id')->orWhere('wc.organisation_id', 0);
            })
            ->whereNotNull('wc.organisation_uuid')
            ->update(['wc.organisation_id' => DB::raw('o.id')]);



        // ============================================
        // DONE
        // ============================================

        $this->info('✅ WasteCollection ID/UUID sync complete!');

        $this->info('Clearing cache...');
        Artisan::call('cache:clear');

        $this->info('✅ Cache cleared!');
    }
}
