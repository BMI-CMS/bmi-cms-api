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
        Schema::create('receipt_encodings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('contact_recording_id')
                ->nullable()
                ->constrained('contact_recordings')
                ->cascadeOnDelete();
            $table->foreignId('for_repossession_id')
                ->nullable()
                ->constrained('for_repossessions')
                ->cascadeOnDelete();
            $table->foreignId('collection_id')
                ->nullable()
                ->constrained('collections')
                ->nullOnDelete();

            $table->string('ar_number', 50)->index();
            $table->dateTime('ar_date');
            $table->decimal('amount', 15, 2)->default(0.00);;
            $table->string('receipt_image_path')->nullable(); // Stores file path / S3 key
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
        Schema::dropIfExists('receipt_encodings');
    }
};
