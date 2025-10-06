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
            $table->string('structure_type')->nullable()->change();
            $table->string('collection_frequency')->nullable()->change();
            $table->integer('bin_count')->nullable()->change();
            $table->string('bin_type')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->string('head_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_points', function (Blueprint $table) {
            $table->string('structure_type')->nullable(false)->change();
            $table->string('collection_frequency')->nullable(false)->change();
            $table->integer('bin_count')->nullable(false)->change();
            $table->string('bin_type')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->text('notes')->nullable(false)->change();
            $table->string('head_name')->nullable(false)->change();
        });
    }
};
