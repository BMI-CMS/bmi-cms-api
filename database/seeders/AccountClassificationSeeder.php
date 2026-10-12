<?php

namespace Database\Seeders;

use App\Models\AccountClassification;
use Illuminate\Database\Seeder;

/**
 * Baseline classifications and their positions in the assigned-accounts response (the existing API contract).
 *
 * Insert-only: a label is created only when no row exists for it (soft-deleted rows included), so a rerun never
 * overwrites a classification that was changed later.
 */
class AccountClassificationSeeder extends Seeder
{
    private const BASELINE = [
        ['prioritization', 'For Repossession', ['prioritization', 'for_repossession']],
        ['prioritization', 'Forced Prioritized', ['prioritization', 'next_action_plan', 'force_prioritized']],
        ['prioritization', 'Promise To Pay', ['prioritization', 'promise_to_pay']],
        ['prioritization', 'Skiptrace', ['prioritization', 'next_action_plan', 'for_skiptrace']],
        ['prioritization', 'Notice & Demand Letter', ['prioritization', 'next_action_plan', 'notice_and_demand_letter']],
        ['prioritization', 'FDD/NS', ['prioritization', 'fdd_ns']],
        ['prioritization', 'Priority 1 Accounts', ['prioritization', 'priority_1_accounts']],
        ['regular', 'DPD 1-30 Days', ['regular', 'dpd_1_30_days']],
        ['regular', 'NP1', ['regular', 'np_1']],
        ['regular', 'NP0', ['regular', 'np_0']],
    ];

    public function run(): void
    {
        foreach (self::BASELINE as [$parent, $label, $responsePath]) {
            AccountClassification::withTrashed()->firstOrCreate(
                ['label' => $label],
                ['parent' => $parent, 'response_path' => $responsePath, 'is_active' => true],
            );
        }
    }
}
