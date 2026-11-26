<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outboxes', function (Blueprint $table) {
            $table->id();

            $table->uuid('idempotency_key')->unique();
            $table->string('destination_system', 50);
            $table->string('event_type', 100);
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->json('payload');
            $table->string('status', 20)->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(10);
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->uuid('processing_token')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_retry_at']);
            $table->index(['status', 'locked_until']);
            $table->index(
                [
                    'destination_system',
                    'reference_type',
                    'reference_id'
                ],
                'outbox_destination_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outboxes');
    }
};
