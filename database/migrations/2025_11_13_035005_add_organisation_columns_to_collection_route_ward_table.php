<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_route_ward', 'organisation_id')) {
                $table->unsignedBigInteger('organisation_id')->nullable()->after('ward_uuid');
            }
            if (!Schema::hasColumn('collection_route_ward', 'organisation_uuid')) {
                $table->uuid('organisation_uuid')->nullable()->after('organisation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collection_route_ward', function (Blueprint $table) {
            $table->dropColumn(['organisation_id', 'organisation_uuid']);
        });
    }
};
