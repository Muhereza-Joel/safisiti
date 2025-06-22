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
        Schema::create('cells', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('name');

            // Reference IDs without foreign key constraints
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Composite unique index for ward_id + name
            $table->unique(['ward_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cells');
    }
};
