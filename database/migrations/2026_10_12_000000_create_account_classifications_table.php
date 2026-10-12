<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The classification labels and where each one appears in the assigned-accounts response.
     * response_path is the API contract, e.g. ["regular", "np_1"] or ["prioritization", "next_action_plan", "force_prioritized"].
     */
    public function up(): void
    {
        Schema::create('account_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('parent', 50);
            $table->string('label', 100)->unique();
            $table->json('response_path');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_classifications');
    }
};
