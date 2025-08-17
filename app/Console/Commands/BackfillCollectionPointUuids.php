<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\CollectionPoint;
use App\Models\Organisation;
use Illuminate\Console\Command;
use App\Models\Ward;

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
        CollectionPoint::whereNull('ward_uuid')
            ->orWhereNull('cell_uuid')
            ->orWhereNull('organisation_uuid')
            ->chunk(500, function ($points) {
                foreach ($points as $p) {
                    if (empty($p->ward_uuid) && $p->ward_id) {
                        $p->ward_uuid = optional(Ward::find($p->ward_id))->uuid;
                    }
                    if (empty($p->cell_uuid) && $p->cell_id) {
                        $p->cell_uuid = optional(Cell::find($p->cell_id))->uuid;
                    }
                    if (empty($p->organisation_uuid) && $p->organisation_id) {
                        $p->organisation_uuid = optional(Organisation::find($p->organisation_id))->uuid;
                    }
                    $p->save();
                }
            });

        $this->info('✅ CollectionPoint UUIDs backfilled successfully!');
    }
}
