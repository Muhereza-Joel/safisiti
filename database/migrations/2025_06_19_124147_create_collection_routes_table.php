<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('collection_routes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'bi-weekly', 'monthly', 'custom']);
            $table->json('collection_days')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->text('notes')->nullable();

            // Reference without foreign key constraint
            $table->unsignedBigInteger('organisation_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Pivot table for route-ward relationship (no foreign key constraints)
        Schema::create('collection_route_ward', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_route_id')->nullable();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->integer('collection_order')->nullable(); // Optional: to specify order of collection
            $table->timestamps();

            // Prevent duplicate relationships
            $table->unique(['collection_route_id', 'ward_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_route_ward');
        Schema::dropIfExists('collection_routes');
    }
};
