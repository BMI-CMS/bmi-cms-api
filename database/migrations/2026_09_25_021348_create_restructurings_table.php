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
        Schema::create('restructurings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('contact_recording_id')
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('monthly_amortization', 15, 2);
            $table->decimal('interest_rate', 15, 2);
            $table->decimal('monthly_amortization', 15, 2);
            $table->string('terms', 30);
            $table->decimal('new_monthly_amortization', 15, 2)->nullable();
            $table->decimal('new_interest_rate', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('restructuring_form')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restructurings');
    }
};
