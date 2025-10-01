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
        Schema::create('point_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('scan_type');
            $table->string('scanned_value');
            $table->string('extracted_uuid')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('scanned_at')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->uuid('user_uuid')->nullable();

            $table->unsignedBigInteger('organisation_id')->index();
            $table->uuid('organisation_uuid')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_scans');
    }
};
