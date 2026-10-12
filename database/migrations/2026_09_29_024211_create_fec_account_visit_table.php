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
        Schema::create('fec_account_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fec_accounts_id')
                ->constrained('fec_accounts')
                ->cascadeOnDelete();
            $table->boolean('is_customer_available');
            $table->dateTime('next_visit_date')->nullable();
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
        Schema::dropIfExists('fec_account_visit');
    }
};
