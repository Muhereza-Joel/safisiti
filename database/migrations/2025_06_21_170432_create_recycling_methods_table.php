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
        Schema::create('recycling_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Unique identifier for the recycling method
            $table->string('name'); // e.g. "Composting", "Mechanical Recycling", "Incineration with Energy Recovery"
            $table->text('description')->nullable();
            $table->foreignId('organisation_id')->nullable()->constrained()->nullOnDelete(); // Organisation that owns this method
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recycling_methods');
    }
};
