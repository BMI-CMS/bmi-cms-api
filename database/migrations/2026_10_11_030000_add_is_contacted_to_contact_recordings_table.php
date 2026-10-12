<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors accounts.is_contacted (boolean, default false, indexed) for a contact recording's
     * next action: whether the account was visited on that next_action_date. Existing rows
     * keep their data and start as false (not visited).
     */
    public function up(): void
    {
        Schema::table('contact_recordings', function (Blueprint $table) {
            $table->boolean('is_contacted')->default(false)->index()->after('next_action_date');
        });
    }

    public function down(): void
    {
        Schema::table('contact_recordings', function (Blueprint $table) {
            $table->dropIndex(['is_contacted']);
            $table->dropColumn('is_contacted');
        });
    }
};
