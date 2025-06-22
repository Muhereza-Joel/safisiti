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
        Schema::create('collection_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('collection_batch_number')->unique();
            $table->foreignId('vehicle_id');
            $table->enum('status', ['not-delivered', 'delivered'])->default('not-delivered');
            $table->unsignedBigInteger('organisation_id')->nullable(); // Organisation that owns this method
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_batches');
    }
};
