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
        Schema::create('deposit_encodings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();
            $table->string('payment_channel', 50);
            $table->string('depository_account', 50);
            $table->string('transaction_reference_number', 100);
            $table->date('deposit_date');
            $table->decimal('total_deposit_amount', 15, 2);
            $table->decimal('deposit_charge', 15, 2);
            $table->decimal('deposit_amount', 15, 2);
            $table->string('deposit_slip', 255)->nullable();
            $table->string('status', 20);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_encodings');
    }
};
