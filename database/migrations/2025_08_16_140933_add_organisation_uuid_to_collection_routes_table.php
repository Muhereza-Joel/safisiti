<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_routes', function (Blueprint $table) {
            $table->uuid('organisation_uuid')->nullable()->after('organisation_id');
        });
    }

    public function down(): void
    {
        Schema::table('collection_routes', function (Blueprint $table) {
            $table->dropColumn('organisation_uuid');
        });
    }
};
