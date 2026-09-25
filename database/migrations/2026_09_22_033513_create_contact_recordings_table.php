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
        Schema::create('contact_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->string('follow_up_mode', 30);
            $table->string('action_taken', 30);
            $table->string('reason_for_default', 50)->nullable();
            $table->string('next_action_plan', 30)->nullable();
            $table->string('assigned_support_cc', 10)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('geotagging', 255)->nullable();
            $table->unsignedBigInteger('recorded_by')->index();
            $table->dateTime('contact_date');
            $table->dateTime('next_action_date')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_recordings');
    }
};
