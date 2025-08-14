<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            // Add the UUID columns
            $table->uuid('collection_route_uuid')->nullable()->after('collection_route_id');
            $table->uuid('ward_uuid')->nullable()->after('ward_id');

            // Add foreign key constraints for UUIDs
            $table
                ->foreign('collection_route_uuid')
                ->references('uuid')
                ->on('collection_routes')
                ->onDelete('cascade');

            $table
                ->foreign('ward_uuid')
                ->references('uuid')
                ->on('wards')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['collection_route_uuid']);
            $table->dropForeign(['ward_uuid']);

            // Drop columns
            $table->dropColumn(['collection_route_uuid', 'ward_uuid']);
        });
    }
};
