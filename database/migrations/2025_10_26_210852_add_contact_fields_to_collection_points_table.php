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
        Schema::table('collection_points', function (Blueprint $table) {
            $table->string('custom_name')->nullable()->after('parent_uuid');
            $table->string('caretaker_name')->nullable()->after('custom_name');
            $table->string('caretaker_phone')->nullable()->after('caretaker_name');
            $table->string('alternate_contact_name')->nullable()->after('caretaker_phone');
            $table->string('alternate_contact_phone')->nullable()->after('alternate_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_points', function (Blueprint $table) {
            $table->dropColumn([
                'custom_name',
                'caretaker_name',
                'caretaker_phone',
                'alternate_contact_name',
                'alternate_contact_phone',
            ]);
        });
    }
};
