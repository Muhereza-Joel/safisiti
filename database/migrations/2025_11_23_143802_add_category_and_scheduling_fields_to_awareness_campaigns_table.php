<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awareness_campaigns', function (Blueprint $table) {
            // Categories
            $table->string('category')->nullable()->after('description');

            // Scheduling and Status
            // Recommended statuses: draft, scheduled, active, completed, cancelled
            $table->string('status')->default('draft')->after('location');
            $table->date('start_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('awareness_campaigns', function (Blueprint $table) {
            $table->dropColumn(['category', 'status', 'start_date', 'end_date']);
        });
    }
};
