<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'users',
        'contacts',
        'preferences',
        'bug_reports',
        'collection_routes',
        'wards',
        'collection_route_ward',
        'cells',
        'collection_points',
        'waste_types',
        'recycling_methods',
        'dumping_sites',
        'recycling_centers',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->index('created_at', $tableName . '_created_at_index');
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->index('updated_at', $tableName . '_updated_at_index');
                }
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->dropIndex($tableName . '_created_at_index');
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->dropIndex($tableName . '_updated_at_index');
                }
            });
        }
    }
};
