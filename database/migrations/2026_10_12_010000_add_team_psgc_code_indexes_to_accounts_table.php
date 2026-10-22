<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'accounts_assigned_ch_id_psgc_code_index' => ['assigned_ch_id', 'psgc_code'],
        'accounts_assigned_am_ar_id_psgc_code_index' => ['assigned_am_ar_id', 'psgc_code'],
        'accounts_assigned_rm_dh_id_psgc_code_index' => ['assigned_rm_dh_id', 'psgc_code'],
    ];

    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            foreach (self::INDEXES as $name => $columns) {
                if (!Schema::hasIndex('accounts', $name)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    public function down(): void
    {
        // MySQL drops the single-column foreign key indexes once a composite index covers them, and
        // refuses to drop the composite one while it is the only index behind the foreign key.
        Schema::table('accounts', function (Blueprint $table) {
            foreach (self::INDEXES as $name => $columns) {
                if (Schema::hasIndex('accounts', $name)) {
                    $table->index($columns[0], "accounts_{$columns[0]}_foreign");
                    $table->dropIndex($name);
                }
            }
        });
    }
};
