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
            //add new terms column.
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('contact_recording_id')
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->decimal('original_balance', 15, 2);
            $table->decimal('restructured_balance', 15, 2);
            $table->decimal('original_monthly_amortization', 15, 2);
            $table->decimal('new_monthly_amortization', 15, 2);
            $table->decimal('original_interest_rate', 15, 2);
            $table->decimal('new_interest_rate', 15, 2);
            $table->string('remarks', 255);
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
        Schema::dropIfExists('restructurings');
    }
};
