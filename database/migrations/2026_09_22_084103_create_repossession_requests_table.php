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
        Schema::create('repossession_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_recording_id')
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->boolean('is_first_level_approved')->default(false);
            $table->string('first_level_approved_by', 10)->nullable();
            $table->dateTime('first_level_review_at')->nullable();
            $table->string('first_level_approved_remarks', 255)->nullable();
            $table->boolean('is_second_level_approved')->default(false);
            $table->string('second_level_approved_by', 10)->nullable();
            $table->dateTime('second_level_review_at')->nullable();
            $table->string('second_level_approved_remarks', 255)->nullable();
            $table->boolean('is_third_level_approved')->default(false);
            $table->string('third_level_approved_by', 10)->nullable();
            $table->dateTime('third_level_review_at')->nullable();
            $table->string('third_level_approved_remarks', 255)->nullable();
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
        Schema::dropIfExists('repossession_requests');
    }
};
