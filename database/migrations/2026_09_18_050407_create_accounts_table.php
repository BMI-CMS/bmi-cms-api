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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 15)->index(); //
            //$table->unsignedBigInteger('customer_id')->index();
            // $table->unsignedBigInteger('cust_code')->index();
            $table->string('customer_name');
            $table->decimal('monthly_amortization', 15, 2);
            $table->decimal('past_due_balance', 15, 2);
            $table->integer('day_past_due')->default(0);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('soa_balance', 15, 2);
            $table->decimal('total_due_balance', 15, 2);
            $table->date('last_payment_date');
            $table->string('dpd_bucket', 50);
            $table->string('payment_status', 30)->default('WITH PAYMENT');
            $table->string('payment_history', 30)->default('UPDATED');
            $table->string('asset', 255);
            $table->string('psgc_code', 10);
            $table->boolean('pre_qualified_for_restructuring')->default(false);
            $table->boolean('is_force_prioritized')->default(false);
            $table->boolean('is_contacted')->default(false)->index();
            $table->foreignId('assigned_cc_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_ch_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_am_ar_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_rm_dh_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->date('due_date');
            $table->date('send_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
