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
        Schema::create('non_starter_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('contact_recording_id')
                ->unique()
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->decimal('monthly_amortization', 15, 2);
            $table->decimal('total_payment', 15, 2);
            $table->decimal('shortfall_amount', 15, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_starter_payments');
    }
};
