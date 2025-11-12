<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id');
            $table->boolean('is_suspended')->default(false)->after('is_active');
            $table->timestamp('suspended_until')->nullable()->after('is_suspended');
            $table->string('suspension_reason')->nullable()->after('suspended_until');
            $table->timestamp('last_login_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'is_suspended',
                'suspended_until',
                'suspension_reason',
                'last_login_at',
            ]);
        });
    }
};
