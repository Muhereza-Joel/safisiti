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
        Schema::create('awareness_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('date_conducted');
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->uuid('organisation_uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('user_uuid')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awareness_campaigns');
    }
};
