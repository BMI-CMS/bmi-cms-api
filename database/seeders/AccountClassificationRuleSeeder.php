<?php

namespace Database\Seeders;

use App\Models\AccountClassificationRule;
use Illuminate\Database\Seeder;

/**
 * The approved account classification rules (docs/general-business-rules.md, sections 4-6).
 * The DPD bucket is the primary reference: DPD 0 = NP0, DPD 1-30 = DPD 1-30 Days, DPD 31-60 = NP1,
 * DPD 61-90 = Priority 1 Accounts, DPD 91-120 and higher = For Repossession. NP is not used.
 *
 * Rules are evaluated by ascending precedence and the first match wins:
 *   10      For Repossession      DPD 91-120 and above, never FIRST DUE DATE / NON-STARTER
 *   20      Forced Prioritized    accounts.is_force_prioritized
 *   30-32   Promise To Pay, Skiptrace, Notice & Demand Letter (the recording's next action plan)
 *   33      classification = null  fallback plans: use the recording's current_category
 *   40-80   base rules: FDD/NS > Priority 1 Accounts > DPD 1-30 Days > NP1 > NP0
 *
 * Safe to rerun: rules are matched by parent + classification and reset to these values.
 * Edit the table (conditions, precedence, is_active) to change the behavior without code changes.
 */
class AccountClassificationRuleSeeder extends Seeder
{
    private const FDD_NS = ['FIRST DUE DATE', 'NON-STARTER'];

    private const FALLBACK_PLANS = [
        'FOR FIELD VISIT',
        'FOR FILING BRGY BLOTTER/COMPLAIN',
        'TO ATTEND BARANGAY HEARING',
        'FOR DISCREPANCY ADJUSTMENT',
        'FOR ASSUMPTION OF UNIT',
        'FOR WRITE OFF (MINIMAL BALANCE)',
    ];

    private const FOR_REPO_BUCKETS = [
        'DPD 91-120', 'DPD 121-150', 'DPD 151-180', 'DPD 181-210', 'DPD 211-240',
        'DPD 241-270', 'DPD 271-300', 'DPD 301-330', 'DPD 331-360', 'DPD MORE THAN 360',
    ];

    public function run(): void
    {
        // The rules refer to these labels, so their baseline must exist first.
        $this->call(AccountClassificationSeeder::class);

        $rules = [
            [10, 'Prioritization', 'For Repossession', [
                ['field' => 'dpd_bucket', 'operator' => 'in', 'value' => self::FOR_REPO_BUCKETS],
                ['field' => 'payment_status', 'operator' => 'not_in', 'value' => self::FDD_NS],
            ]],
            [20, 'Prioritization', 'Forced Prioritized', [
                ['field' => 'is_force_prioritized', 'operator' => '=', 'value' => true],
            ]],
            [30, 'Prioritization', 'Promise To Pay', [
                ['field' => 'next_action_plan', 'operator' => '=', 'value' => 'PROMISE TO PAY'],
            ]],
            [31, 'Prioritization', 'Skiptrace', [
                ['field' => 'next_action_plan', 'operator' => '=', 'value' => 'FOR SKIPTRACE'],
            ]],
            [32, 'Prioritization', 'Notice & Demand Letter', [
                ['field' => 'next_action_plan', 'operator' => '=', 'value' => 'FOR SERVICE OF COLLECTION NOTICE'],
            ]],
            [33, 'Prioritization', null, [
                ['field' => 'next_action_plan', 'operator' => 'in', 'value' => self::FALLBACK_PLANS],
            ]],
            [40, 'Prioritization', 'FDD/NS', [
                ['field' => 'payment_status', 'operator' => 'in', 'value' => self::FDD_NS],
            ]],
            [50, 'Prioritization', 'Priority 1 Accounts', [
                ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 61-90'],
            ]],
            [60, 'Regular', 'DPD 1-30 Days', [
                ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 1-30'],
            ]],
            [70, 'Regular', 'NP1', [
                ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 31-60'],
            ]],
            [80, 'Regular', 'NP0', [
                ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 0'],
            ]],
        ];

        foreach ($rules as [$precedence, $parent, $classification, $conditions]) {
            $rule = AccountClassificationRule::withTrashed()->updateOrCreate(
                ['parent' => $parent, 'classification' => $classification],
                ['precedence' => $precedence, 'conditions' => ['all' => $conditions], 'is_active' => true],
            );

            if ($rule->trashed()) {
                $rule->restore();
            }
        }
    }
}
