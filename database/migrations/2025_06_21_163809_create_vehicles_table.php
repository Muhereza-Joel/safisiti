<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Unique identifier for the vehicle
            $table->string('registration_number')->unique(); // e.g., UBD 123F
            $table->string('model')->nullable();              // e.g., "Isuzu FSR"
            $table->string('capacity')->nullable();           // e.g., "5 tons"
            $table->string('type')->nullable();               // e.g., "Garbage Truck"
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->nullOnDelete();          // Vehicle assigned to a user
            $table->foreignId('organisation_id')->nullable()->constrained()->nullOnDelete();
            $table->softDeletes(); // Soft delete support
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
