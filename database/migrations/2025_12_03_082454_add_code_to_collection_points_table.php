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
        Schema::table('collection_points', function (Blueprint $table) {
            // Add the 'code' column right after 'uuid'
            $table->string('code', 20)->nullable()->after('uuid');

            // Add the composite unique index: 'code' must be unique ONLY within a specific 'cell_id'
            $table->unique(['cell_id', 'code']);

            // Add an index for faster lookups
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_points', function (Blueprint $table) {
            $table->dropUnique(['cell_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
