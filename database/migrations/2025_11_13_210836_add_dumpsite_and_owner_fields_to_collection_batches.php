<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDumpsiteAndOwnerFieldsToCollectionBatches extends Migration
{
    public function up()
    {
        Schema::table('collection_batches', function (Blueprint $table) {
            $table->uuid('owner_user_uuid')->nullable()->after('organisation_id');
            $table->uuid('dumpsite_uuid')->nullable()->after('owner_user_uuid');
            $table->decimal('actual_tonnage', 8, 2)->nullable()->after('dumpsite_uuid');
        });
    }

    public function down()
    {
        Schema::table('collection_batches', function (Blueprint $table) {
            $table->dropColumn([
                'owner_user_uuid',
                'dumpsite_uuid',
                'actual_tonnage',
            ]);
        });
    }
}
