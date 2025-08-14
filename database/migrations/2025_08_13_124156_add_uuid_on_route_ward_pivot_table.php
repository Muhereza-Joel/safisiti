<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            $table->uuid()->unique()->after('id');
            $table->uuid('collection_route_uuid')->nullable()->after('collection_route_id');
            $table->uuid('ward_uuid')->nullable()->after('ward_id');
        });
    }

    public function down()
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropColumn('uuid');
            // Drop columns
            $table->dropColumn(['collection_route_uuid', 'ward_uuid']);
        });
    }
};
