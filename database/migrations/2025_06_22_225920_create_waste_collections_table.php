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
        Schema::create('waste_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('amount')->default(0);
            $table->string('units')->default('kg');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('collection_batch_id');
            $table->unsignedBigInteger('collection_point_id')->nullable();
            $table->unsignedBigInteger('waste_type_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_collections');
    }
};
