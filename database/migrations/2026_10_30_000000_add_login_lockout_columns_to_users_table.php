<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the login lockout columns to the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('phone_imei');
            $table->timestamp('login_frozen_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('locked_at')->nullable()->after('login_frozen_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'login_frozen_until', 'locked_at']);
        });
    }
};
