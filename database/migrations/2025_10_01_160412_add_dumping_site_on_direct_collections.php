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
        Schema::table('direct_collections', function (Blueprint $table) {
            $table->unsignedBigInteger('dumping_site_id')->nullable()->index()->after('waste_type_id');
            $table->uuid('dumping_site_uuid')->nullable()->index()->after('waste_type_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_collections', function (Blueprint $table) {
            $table->dropColumn(['dumping_site_id', 'dumping_site_uuid']);
        });
    }
};
