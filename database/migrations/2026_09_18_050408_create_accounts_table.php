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
            $table->string('account_number', 15)->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('customer_name');
            $table->decimal('monthly_amortization', 15, 2);
            $table->decimal('past_due_balance', 15, 2);
            $table->integer('days_past_due');
            $table->string('dpd_bucket', 50);
            $table->string('no_of_non_payments', 100);
            $table->decimal('outstanding_balance', 15, 2);
            $table->date('last_payment_date');
            $table->decimal('asset', 15, 2);
            $table->boolean('is_force_prioritized')->default(false);
            $table->foreignId('assigned_cc_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_ch_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_am_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->foreignId('assigned_dh_id')
                ->constrained('cms_users')
                ->cascadeOnDelete();
            $table->date('assigned_date')->nullable();
            $table->date('follow_up_date')->nullable();
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
        Schema::dropIfExists('accounts');
    }
};
