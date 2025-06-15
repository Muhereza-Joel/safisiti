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
        Schema::create('collection_points', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('head_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->unsignedBigInteger('ward_id');
            $table->unsignedBigInteger('cell_id');
            $table->text('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('structure_type');
            $table->integer('household_size');
            $table->string('waste_type');
            $table->string('collection_frequency');
            $table->integer('bin_count');
            $table->string('bin_type');
            $table->date('last_collection_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('organisation_id');
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ward_id')->references('id')->on('wards')->onDelete('cascade');
            $table->foreign('cell_id')->references('id')->on('cells')->onDelete('cascade');
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');

            // Indexes
            $table->index(['ward_id', 'cell_id']);
            $table->index('organisation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_points');
    }
};
