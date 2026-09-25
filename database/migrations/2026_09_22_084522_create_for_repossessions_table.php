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
        Schema::create('for_repossessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('repossession_request_id')
                ->constrained('repossession_requests')
                ->cascadeOnDelete();
            $table->string('stockyard', 50)->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->string('reason_for_unrepossessed', 50)->nullable();
            $table->string('status_name', 20);
            $table->boolean('status')->default(false);
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
        Schema::dropIfExists('for_repossessions');
    }
};
