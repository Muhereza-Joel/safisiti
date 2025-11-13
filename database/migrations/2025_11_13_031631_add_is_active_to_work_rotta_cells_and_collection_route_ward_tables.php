<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_rotta_cells', function (Blueprint $table) {
            if (!Schema::hasColumn('work_rotta_cells', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        Schema::table('collection_route_ward', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_route_ward', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('collection_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_rotta_cells', function (Blueprint $table) {
            if (Schema::hasColumn('work_rotta_cells', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('collection_route_ward', function (Blueprint $table) {
            if (Schema::hasColumn('collection_route_ward', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
