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
            $table->foreignId('customer_details_id')
                ->constrained('customer_details')
                ->cascadeOnDelete();
            $table->string('unit_lot_block')->nullable();
            $table->string('street_name')->nullable();
            $table->string('subdivision_village')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality');
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code', 20)->nullable();
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
