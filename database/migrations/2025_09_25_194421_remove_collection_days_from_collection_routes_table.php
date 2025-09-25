<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_routes', function (Blueprint $table) {
            $table->dropColumn('collection_days');
        });
    }

    public function down(): void
    {
        Schema::table('collection_routes', function (Blueprint $table) {
            // Recreate it as a JSON column in case of rollback
            $table->json('collection_days')->nullable();
        });
    }
};
