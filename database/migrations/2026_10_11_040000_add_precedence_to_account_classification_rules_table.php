<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evaluation order of the rules: lowest first, the first matching rule decides the
     * classification. Nullable so existing rows survive; a rule without a precedence is
     * reported as invalid configuration instead of being guessed.
     */
    public function up(): void
    {
        Schema::table('account_classification_rules', function (Blueprint $table) {
            $table->unsignedSmallInteger('precedence')->nullable()->after('classification');
        });
    }

    public function down(): void
    {
        Schema::table('account_classification_rules', function (Blueprint $table) {
            $table->dropColumn('precedence');
        });
    }
};
