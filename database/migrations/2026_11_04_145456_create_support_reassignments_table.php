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
        Schema::create('support_reassignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('contact_recording_id')
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->foreignId('new_assigned_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->dateTime('next_action_date')->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_reassignments');
    }
};
