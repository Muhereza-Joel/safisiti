<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_rotta', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->uuid('route_uuid')->index();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('shift_type');
            $table->string('status')->nullable();
            $table->string('attendance_status')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->unsignedTinyInteger('performance_rating')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('inspector_user_uuid')->nullable();
            $table->uuid('service_provider_user_uuid')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->uuid('organisation_uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_rotta_cells', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->uuid('work_rotta_uuid')->index();
            $table->uuid('cell_uuid');
            $table->string('status');
            $table->unsignedBigInteger('organisation_id');
            $table->uuid('organisation_uuid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_rotta_cells');
        Schema::dropIfExists('work_rotta');
    }
};
