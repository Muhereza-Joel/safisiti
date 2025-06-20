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
            $table->foreignId('organisation_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });

        // Pivot table for route-ward relationship
        Schema::create('collection_route_ward', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_route_id')->constrained()->onDelete('cascade');
            $table->foreignId('ward_id')->constrained()->onDelete('cascade');
            $table->integer('collection_order')->nullable(); // Optional: to specify order of collection
            $table->timestamps();

            $table->unique(['collection_route_id', 'ward_id']); // Prevent duplicate relationships
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_route_ward');
        Schema::dropIfExists('collection_routes');
    }
};
