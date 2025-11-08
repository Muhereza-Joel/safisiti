<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            $table->dropUnique('collection_route_ward_collection_route_id_ward_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            $table->unique(['collection_route_id', 'ward_id'], 'collection_route_ward_collection_route_id_ward_id_unique');
        });
    }
};
