<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_inboxes', function (Blueprint $table) {

            $table->id();

            $table->string('source_system', 50);
            $table->string('event_type', 100);
            $table->uuid('idempotency_key');

            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');

            $table->json('payload');
            $table->string('payload_hash', 64);

            $table->json('response')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['source_system', 'idempotency_key'],
                'inbox_source_idempotency_unique'
            );

            $table->index(
                ['reference_type', 'reference_id'],
                'inbox_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_inboxes');
    }
};
