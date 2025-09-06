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
        Schema::create('direct_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();          // e.g. person/source name
            $table->string('contact')->nullable();          // e.g. person phone number
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('units')->nullable();         // e.g. kg, bags
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('waste_type_id')->nullable();
            $table->uuid('waste_type_uuid')->nullable();
            $table->boolean('segregated')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('user_uuid')->nullable();
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
        Schema::dropIfExists('direct_collections');
    }
};
