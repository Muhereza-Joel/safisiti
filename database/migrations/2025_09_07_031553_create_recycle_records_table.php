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
        Schema::create('recycle_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('recycling_center_id')->nullable();
            $table->uuid('recycling_center_uuid')->nullable();
            $table->unsignedBigInteger('recycling_method_id')->nullable();
            $table->uuid('recycling_method_uuid')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('units')->default('kgs');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->uuid('organisation_uuid')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recycle_records');
    }
};
