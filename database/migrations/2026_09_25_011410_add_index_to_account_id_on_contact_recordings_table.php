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
        Schema::table('contact_recordings', function (Blueprint $table) {
            $table->index('account_id', 'idx_contact_recordings_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_id_on_contact_recordings', function (Blueprint $table) {
            //
        });
    }
};
