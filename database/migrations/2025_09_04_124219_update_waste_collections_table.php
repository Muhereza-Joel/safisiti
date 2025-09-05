<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('waste_collections', function (Blueprint $table) {
            $table->uuid('collection_batch_uuid')->nullable()->after('collection_batch_id');
            $table->uuid('collection_point_uuid')->nullable()->after('collection_point_id');
            $table->uuid('waste_type_uuid')->nullable()->after('waste_type_id');
            $table->uuid('user_uuid')->nullable()->after('user_id');
            $table->uuid('organisation_uuid')->nullable()->after('organisation_id');
            $table->boolean('segregated')->default(false)->after('organisation_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_collections', function (Blueprint $table) {
            $table->dropColumn([
                'collection_batch_uuid',
                'collection_point_uuid',
                'waste_type_uuid',
                'user_uuid',
                'organisation_uuid',
                'segregated',
            ]);
        });
    }
};
