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
        Schema::create('fec_customer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fec_accounts_id')
                ->constrained('fec_accounts')
                ->cascadeOnDelete();
            $table->json('table_a_section_1')->nullable();
            $table->json('table_a_section_2')->nullable();
            $table->string('table_a_section_3', 255)->nullable();
            $table->string('table_a_section_4', 35)->nullable();
            $table->json('table_b_section_5')->nullable();
            $table->json('table_b_section_6')->nullable();
            $table->string('fec_form')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->date('visit_date');
            $table->date('submit_date')->nullable();
            $table->string('status', 20)->default('FOR APPROVALS');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fec_customer_evaluation');
    }
};
