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
        Schema::create('collecting_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->unique()
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->string('psgc_code', 10);
            $table->string('unit_lot_block', 255)->nullable();
            $table->string('street_name', 255)->nullable();
            $table->string('subdivision_village', 255)->nullable();
            $table->string('province', 255)->nullable();
            $table->string('postal_code', 4)->nullable();
            $table->string('barangay', 255);
            $table->string('city_municipality', 255);
            $table->string('region', 255);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collecting_address');
    }
};
