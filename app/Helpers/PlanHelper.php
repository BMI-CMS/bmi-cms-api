<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\ContactRecording;
use App\Models\ForRepossession;
use App\Models\RepossessionRequest;
use App\Models\ReceiptEncoding;

class PlanHelper
{
    // ==========================================
    // Top-Level Account Categories
    // ==========================================
    public const CATEGORY_PRIORITY = 'Priority Accounts';
    public const CATEGORY_REGULAR  = 'Regular Accounts';

    // ==========================================
    // Priority Account Subcategories
    // ==========================================
    public const TYPE_PTP              = 'Promise to Pay (PTP) Accounts';
    public const TYPE_FOR_REPOSSESSION = 'For Repossession Accounts';
    public const TYPE_PRIORITY_1       = 'Priority 1 accounts';
    public const TYPE_NEXT_ACTION_PLAN = 'Next Action Plan Accounts';
    public const TYPE_FDD_NS           = 'FDD / NS';

    // ==========================================
    // Regular Account Subcategory
    // ==========================================
    public const TYPE_REGULAR = 'Regular Accounts';

    // ==========================================
    // Specific Subtypes
    // ==========================================
    public const SUBTYPE_SKIP_TRACE         = 'Skip trace';
    public const SUBTYPE_NOTICE_DEMAND      = 'Notice and demand letter';
    public const SUBTYPE_FORCED_PRIORITIZED = 'Forced Prioritized';
    public const SUBTYPE_FDD                = 'FDD on due date';
    public const SUBTYPE_NON_STARTER        = 'Non Starter';

    /**
     * Filters/categorizes each plan/account into its respective category based on rules.
     *
     * @param mixed $plans Collection, array, or null (defaults to Account::all() if null)
     * @param bool $hierarchical When true, groups by Main Category ('Priority Accounts', 'Regular Accounts')
     *                           then by Subcategories. When false (default), groups by all Subcategories.
     * @return Collection
     */
    public static function filterEachPlan(mixed $plans = null, bool $hierarchical = false): Collection
    {
        // 1. Resolve plans collection
        if ($plans === null) {
            $plans = class_exists(Account::class) ? Account::all() : collect();
        } elseif (is_array($plans)) {
            $plans = collect($plans);
        } elseif (!$plans instanceof Collection) {
            $plans = collect($plans);
        }

        // 2. Preload database relations for all accounts to avoid N+1 queries
        $context = self::preloadContext($plans);

        // 3. Initialize category buckets preserving predefined rule order
        if ($hierarchical) {
            $results = [
                self::CATEGORY_PRIORITY => [
                    self::TYPE_PTP              => collect(),
                    self::TYPE_FOR_REPOSSESSION => collect(),
                    self::TYPE_PRIORITY_1       => collect(),
                    self::TYPE_NEXT_ACTION_PLAN => collect(),
                    self::TYPE_FDD_NS           => collect(),
                ],
                self::CATEGORY_REGULAR  => collect(),
            ];
        } else {
            $results = [
                self::TYPE_PTP              => collect(),
                self::TYPE_FOR_REPOSSESSION => collect(),
                self::TYPE_PRIORITY_1       => collect(),
                self::TYPE_NEXT_ACTION_PLAN => collect(),
                self::TYPE_FDD_NS           => collect(),
                self::TYPE_REGULAR          => collect(),
            ];
        }

        // 4. Classify each plan and place it into its category bucket
        foreach ($plans as $plan) {
            $classification = self::categorizePlan($plan, $context);
            $mainCat = $classification['main_category'];
            $subCat  = $classification['subcategory'];

            // Attach classification metadata directly to the plan item for convenience
            if (is_object($plan)) {
                $plan->main_category = $mainCat;
                $plan->plan_category = $subCat;
                $plan->specific_type = $classification['specific_type'];
                $plan->matched_rules = $classification['matched_rules'];
                if (!isset($plan->type)) {
                    $plan->type = $subCat;
                }
            } elseif (is_array($plan)) {
                $plan['main_category'] = $mainCat;
                $plan['plan_category'] = $subCat;
                $plan['specific_type'] = $classification['specific_type'];
                $plan['matched_rules'] = $classification['matched_rules'];
                if (!isset($plan['type'])) {
                    $plan['type'] = $subCat;
                }
            }

            // Distribute plan into corresponding bucket
            if ($hierarchical) {
                if ($mainCat === self::CATEGORY_PRIORITY) {
                    if (isset($results[self::CATEGORY_PRIORITY][$subCat])) {
                        $results[self::CATEGORY_PRIORITY][$subCat]->push($plan);
                    } else {
                        $results[self::CATEGORY_PRIORITY][$subCat] = collect([$plan]);
                    }
                } else {
                    $results[self::CATEGORY_REGULAR]->push($plan);
                }
            } else {
                if (isset($results[$subCat])) {
                    $results[$subCat]->push($plan);
                } else {
                    $results[$subCat] = collect([$plan]);
                }
            }
        }

        if ($hierarchical) {
            $results[self::CATEGORY_PRIORITY] = collect($results[self::CATEGORY_PRIORITY]);
        }

        return collect($results);
    }

    /**
     * Filters plans by a specific category, subcategory, or alias.
     * Backwards-compatible with the original signature while supporting full rule evaluation.
     *
     * @param Collection $plans
     * @param string $type
     * @return Collection
     */
    public static function filterPlansByType(
        Collection $plans,
        string $type
    ): Collection {
        $context = self::preloadContext($plans);

        return $plans->filter(function ($plan) use ($type, $context) {
            // Direct type match if pre-assigned
            $existingType = self::getAttribute($plan, 'type');
            if ($existingType !== null && strcasecmp($existingType, $type) === 0) {
                return true;
            }

            $classification = self::categorizePlan($plan, $context);
            $mainCat     = $classification['main_category'];
            $subCat      = $classification['subcategory'];
            $specific    = $classification['specific_type'];

            // Match full category names
            if (strcasecmp($type, $mainCat) === 0 ||
                strcasecmp($type, $subCat) === 0 ||
                strcasecmp($type, $specific) === 0) {
                return true;
            }

            // Match aliases
            $typeLower = strtolower(trim($type));
            if ($typeLower === 'priority' && $mainCat === self::CATEGORY_PRIORITY) {
                return true;
            }
            if ($typeLower === 'regular' && $mainCat === self::CATEGORY_REGULAR) {
                return true;
            }
            if (in_array($typeLower, ['ptp', 'promise to pay']) && $subCat === self::TYPE_PTP) {
                return true;
            }
            if (in_array($typeLower, ['repo', 'for repo', 'for repossession', 'repossession']) && $subCat === self::TYPE_FOR_REPOSSESSION) {
                return true;
            }
            if (in_array($typeLower, ['priority 1', 'p1']) && $subCat === self::TYPE_PRIORITY_1) {
                return true;
            }
            if (in_array($typeLower, ['next action plan', 'next action']) && $subCat === self::TYPE_NEXT_ACTION_PLAN) {
                return true;
            }
            if (in_array($typeLower, ['fdd', 'ns', 'fdd/ns', 'non starter', 'fdd / ns']) && $subCat === self::TYPE_FDD_NS) {
                return true;
            }

            return false;
        })->values();
    }

    /**
     * Determines the detailed categorization of a single plan/account according to the rules.
     *
     * @param mixed $plan
     * @param array|null $context Preloaded related records
     * @return array
     */
    public static function categorizePlan(mixed $plan, ?array $context = null): array
    {
        $matchedRules = [];

        // 1. Promise to Pay (PTP) Accounts
        // Accounts with an active Promise-to-Pay commitment recorded through Contact Recording within the month.
        if (self::checkPromiseToPay($plan, $context, $matchedRules)) {
            return [
                'main_category' => self::CATEGORY_PRIORITY,
                'subcategory'   => self::TYPE_PTP,
                'specific_type' => self::TYPE_PTP,
                'matched_rules' => $matchedRules,
            ];
        }

        // 2. For Repossession Accounts
        // Accounts with NP3 and above and DPD 91 and above shall be automatically tagged as For Repossession.
        // Repossession Requests approved by the Cluster Head (CH) until the last approver shall automatically be reflected under this category.
        if (self::checkForRepossession($plan, $context, $matchedRules)) {
            return [
                'main_category' => self::CATEGORY_PRIORITY,
                'subcategory'   => self::TYPE_FOR_REPOSSESSION,
                'specific_type' => self::TYPE_FOR_REPOSSESSION,
                'matched_rules' => $matchedRules,
            ];
        }

        // 3. Priority 1 accounts
        // Accounts classified as NP2 and above with a DPD - 61 and above based on the loaded targets.
        if (self::checkPriority1($plan, $context, $matchedRules)) {
            return [
                'main_category' => self::CATEGORY_PRIORITY,
                'subcategory'   => self::TYPE_PRIORITY_1,
                'specific_type' => self::TYPE_PRIORITY_1,
                'matched_rules' => $matchedRules,
            ];
        }

        // 4. Next Action Plan Accounts
        // Skip trace, Notice and demand letter, Forced Prioritized
        $nextActionType = self::checkNextActionPlan($plan, $context, $matchedRules);
        if ($nextActionType) {
            return [
                'main_category' => self::CATEGORY_PRIORITY,
                'subcategory'   => self::TYPE_NEXT_ACTION_PLAN,
                'specific_type' => $nextActionType,
                'matched_rules' => $matchedRules,
            ];
        }

        // 5. FDD / NS
        // FDD on due date (identifies accounts with no payment on or before due date)
        // Non Starter (identifies accounts that did make a payment, but the total amount is less than the required Minimum Installment (MI).)
        $fddNsType = self::checkFddOrNonStarter($plan, $context, $matchedRules);
        if ($fddNsType) {
            return [
                'main_category' => self::CATEGORY_PRIORITY,
                'subcategory'   => self::TYPE_FDD_NS,
                'specific_type' => $fddNsType,
                'matched_rules' => $matchedRules,
            ];
        }

        // 6. Regular Accounts
        // No contact recording, DPD 1 - 30, NP0 and NP1 irregardless of DPD
        if (self::checkRegular($plan, $context, $matchedRules)) {
            return [
                'main_category' => self::CATEGORY_REGULAR,
                'subcategory'   => self::TYPE_REGULAR,
                'specific_type' => self::TYPE_REGULAR,
                'matched_rules' => $matchedRules,
            ];
        }

        // Default fallback based on whether contact recording exists
        $hasContact = self::hasContactRecording($plan, $context);
        return [
            'main_category' => $hasContact ? self::CATEGORY_PRIORITY : self::CATEGORY_REGULAR,
            'subcategory'   => $hasContact ? self::CATEGORY_PRIORITY : self::TYPE_REGULAR,
            'specific_type' => $hasContact ? self::CATEGORY_PRIORITY : self::TYPE_REGULAR,
            'matched_rules' => [$hasContact ? 'Default priority due to active contact history' : 'Default regular account'],
        ];
    }

    /**
     * Returns the specific category string for a plan.
     */
    public static function determineCategory(mixed $plan, ?array $context = null): string
    {
        return self::categorizePlan($plan, $context)['subcategory'];
    }

    /**
     * Returns the main category ('Priority Accounts' or 'Regular Accounts') for a plan.
     */
    public static function determineMainCategory(mixed $plan, ?array $context = null): string
    {
        return self::categorizePlan($plan, $context)['main_category'];
    }

    // ==========================================
    // Rule Verification Methods
    // ==========================================

    /**
     * Rule: Accounts with an active Promise-to-Pay commitment recorded through Contact Recording within the month.
     */
    public static function checkPromiseToPay(mixed $plan, ?array $context, array &$matchedRules): bool
    {
        if (self::getAttribute($plan, 'is_ptp') || self::getAttribute($plan, 'promise_to_pay')) {
            $matchedRules[] = 'Promise to Pay: Explicit PTP flag present on plan';
            return true;
        }

        $recordings = self::getContactRecordings($plan, $context);
        if (empty($recordings)) {
            return false;
        }

        foreach ($recordings as $recording) {
            $contactDate    = self::getAttribute($recording, 'contact_date') ?? self::getAttribute($recording, 'created_at');
            $nextActionDate = self::getAttribute($recording, 'next_action_date');

            $isWithinMonth = self::isWithinMonth($contactDate) || self::isWithinMonth($nextActionDate);

            if ($isWithinMonth && self::hasPtpCommitment($recording)) {
                $matchedRules[] = 'Promise to Pay: Active commitment recorded in Contact Recording within the month';
                return true;
            }
        }

        return false;
    }

    /**
     * Rule:
     * - Accounts with NP3 and above and DPD 91 and above shall be automatically tagged as For Repossession.
     * - Repossession Requests approved by the Cluster Head (CH) until the last approver shall automatically be reflected under this category.
     */
    public static function checkForRepossession(mixed $plan, ?array $context, array &$matchedRules): bool
    {
        // 1. Automatic tagging: NP >= 3 and DPD >= 91
        $np  = self::parseNonPayments(self::getAttribute($plan, 'no_of_non_payments'));
        $dpd = self::parseDaysPastDue($plan);
        $bucket = (string) self::getAttribute($plan, 'dpd_bucket');

        $isDpd91Plus = ($dpd >= 91) || preg_match('/DPD\s*(?:9[1-9]|\d{3,})/i', $bucket);

        if ($np >= 3 && $isDpd91Plus) {
            $matchedRules[] = "For Repossession: Automatically tagged with NP3+ ({$np}) and DPD 91+ ({$dpd} / {$bucket})";
            return true;
        }

        // 2. Check presence in ForRepossession records
        $forRepo = self::getForRepossession($plan, $context);
        if ($forRepo) {
            $matchedRules[] = 'For Repossession: Account exists in For Repossession table/records';
            return true;
        }

        // 3. Check approved RepossessionRequest (Cluster Head through last approver)
        $requests = self::getRepossessionRequests($plan, $context);
        foreach ($requests as $req) {
            $chApproved   = (bool) self::getAttribute($req, 'is_first_level_approved');
            $lastApproved = (bool) self::getAttribute($req, 'is_third_level_approved');

            if ($chApproved && $lastApproved) {
                $matchedRules[] = 'For Repossession: Repossession request approved by Cluster Head (CH) through last approver';
                return true;
            }
        }

        return false;
    }

    /**
     * Rule: Accounts classified as NP2 and above with a DPD - 61 and above based on the loaded targets.
     */
    public static function checkPriority1(mixed $plan, ?array $context, array &$matchedRules): bool
    {
        $np  = self::parseNonPayments(self::getAttribute($plan, 'no_of_non_payments'));
        $dpd = self::parseDaysPastDue($plan);
        $bucket = (string) self::getAttribute($plan, 'dpd_bucket');

        // DPD 61 and above based on loaded targets (DPD >= 61 or target bucket contains 60+ / 61-90)
        $isDpd61Plus = ($dpd >= 61) || ($dpd >= 60) || preg_match('/DPD\s*(?:6[0-9]|[7-9]\d|\d{3,})/i', $bucket);

        if ($np >= 2 && $isDpd61Plus) {
            $matchedRules[] = "Priority 1: NP2+ ({$np}) with DPD 61+ ({$dpd} / {$bucket}) based on loaded targets";
            return true;
        }

        return false;
    }

    /**
     * Rule: Next Action Plan Accounts
     * - Skip trace
     * - Notice and demand letter
     * - Forced Prioritized
     */
    public static function checkNextActionPlan(mixed $plan, ?array $context, array &$matchedRules): ?string
    {
        if (self::getAttribute($plan, 'is_forced_prioritized') || self::getAttribute($plan, 'forced_prioritized')) {
            $matchedRules[] = 'Next Action Plan: Account flagged as Forced Prioritized';
            return self::SUBTYPE_FORCED_PRIORITIZED;
        }

        $fields = [
            self::getAttribute($plan, 'next_action_plan'),
            self::getAttribute($plan, 'action_taken'),
            self::getAttribute($plan, 'remarks'),
        ];

        $recordings = self::getContactRecordings($plan, $context);
        foreach ($recordings as $rec) {
            $fields[] = self::getAttribute($rec, 'next_action_plan');
            $fields[] = self::getAttribute($rec, 'action_taken');
            $fields[] = self::getAttribute($rec, 'remarks');
        }

        foreach ($fields as $field) {
            if (empty($field) || !is_string($field)) {
                continue;
            }

            if (preg_match('/skiptrace|skip\s*trace/i', $field)) {
                $matchedRules[] = "Next Action Plan: Skip trace found in '{$field}'";
                return self::SUBTYPE_SKIP_TRACE;
            }

            if (preg_match('/demand\s*letter|collection\s*notice|atty\'?s\s*letter|notice/i', $field)) {
                $matchedRules[] = "Next Action Plan: Notice and demand letter found in '{$field}'";
                return self::SUBTYPE_NOTICE_DEMAND;
            }

            if (preg_match('/forced\s*prioritized|force\s*priorit/i', $field)) {
                $matchedRules[] = "Next Action Plan: Forced Prioritized found in '{$field}'";
                return self::SUBTYPE_FORCED_PRIORITIZED;
            }
        }

        return null;
    }

    /**
     * Rule: FDD / NS
     * - FDD on due date (identifies accounts with no payment on or before due date)
     * - Non Starter (identifies accounts that did make a payment, but the total amount is less than the required Minimum Installment (MI).)
     */
    public static function checkFddOrNonStarter(mixed $plan, ?array $context, array &$matchedRules): ?string
    {
        $mi = (float) self::getAttribute($plan, 'monthly_amortization', 0);
        $totalPaid = self::getTotalPayments($plan, $context);
        $pastDueBalance = (float) self::getAttribute($plan, 'past_due_balance', 0);
        $dpd = self::parseDaysPastDue($plan);

        // Non Starter: payment made, but total < required Minimum Installment (MI)
        if ($totalPaid > 0 && $mi > 0 && $totalPaid < $mi) {
            $matchedRules[] = "FDD / NS (Non Starter): Paid {$totalPaid}, which is less than required Minimum Installment (MI) of {$mi}";
            return self::SUBTYPE_NON_STARTER;
        }

        // FDD on due date: no payment on or before due date
        if ($totalPaid == 0 && ($pastDueBalance > 0 || $dpd > 0)) {
            $matchedRules[] = 'FDD / NS (FDD on due date): No payment made on or before due date';
            return self::SUBTYPE_FDD;
        }

        return null;
    }

    /**
     * Rule: Regular Accounts
     * - No contact recording
     * - DPD 1 - 30
     * - NP0 and NP1 irregardless of DPD
     */
    public static function checkRegular(mixed $plan, ?array $context, array &$matchedRules): bool
    {
        $hasContact = self::hasContactRecording($plan, $context);
        if ($hasContact) {
            return false;
        }

        $np  = self::parseNonPayments(self::getAttribute($plan, 'no_of_non_payments'));
        $dpd = self::parseDaysPastDue($plan);
        $bucket = (string) self::getAttribute($plan, 'dpd_bucket');

        $isDpd1To30 = ($dpd >= 1 && $dpd <= 30) || in_array($bucket, ['DPD 1-30', 'DPD 30']);
        $isNp0Or1   = ($np === 0 || $np === 1);

        if ($isDpd1To30 || $isNp0Or1) {
            $reasons = ['No contact recording'];
            if ($isDpd1To30) {
                $reasons[] = "DPD 1-30 (DPD: {$dpd})";
            }
            if ($isNp0Or1) {
                $reasons[] = "NP0/NP1 regardless of DPD (NP: {$np})";
            }
            $matchedRules[] = 'Regular Account: ' . implode(', ', $reasons);
            return true;
        }

        return false;
    }

    // ==========================================
    // Context Preloading & Data Extraction
    // ==========================================

    /**
     * Preloads contact recordings, repossession requests, for_repossessions, and receipts
     * in bulk to ensure optimal O(1) in-memory lookups instead of N+1 database queries.
     */
    protected static function preloadContext(Collection $plans): array
    {
        $accountIds = [];
        foreach ($plans as $plan) {
            $id = self::getAccountId($plan);
            if ($id) {
                $accountIds[] = $id;
            }
        }
        $accountIds = array_unique($accountIds);

        $context = [
            'contact_recordings'    => [],
            'for_repossessions'     => [],
            'repossession_requests' => [],
            'receipts'              => [],
        ];

        if (empty($accountIds)) {
            return $context;
        }

        try {
            if (class_exists(ContactRecording::class)) {
                $recordings = ContactRecording::whereIn('account_id', $accountIds)->get();
                $context['contact_recordings'] = $recordings->groupBy('account_id')->all();

                $contactIds = $recordings->pluck('id')->filter()->all();
                if (!empty($contactIds) && class_exists(RepossessionRequest::class)) {
                    $context['repossession_requests'] = RepossessionRequest::whereIn('contact_recording_id', $contactIds)
                        ->get()
                        ->groupBy('contact_recording_id')
                        ->all();
                }
            }

            if (class_exists(ForRepossession::class)) {
                $context['for_repossessions'] = ForRepossession::whereIn('account_id', $accountIds)
                    ->get()
                    ->groupBy('account_id')
                    ->all();
            }

            if (class_exists(ReceiptEncoding::class)) {
                $context['receipts'] = ReceiptEncoding::whereIn('account_id', $accountIds)
                    ->get()
                    ->groupBy('account_id')
                    ->all();
            }
        } catch (\Throwable $e) {
            // Graceful fallback if database tables are not yet migrated
        }

        return $context;
    }

    /**
     * Retrieves contact recordings for an account.
     */
    protected static function getContactRecordings(mixed $plan, ?array $context): array
    {
        $direct = self::getAttribute($plan, 'contact_recordings') ??
                  self::getAttribute($plan, 'contactRecordings') ??
                  self::getAttribute($plan, 'contact_recording');
        if ($direct !== null) {
            return is_iterable($direct) ? (is_array($direct) ? $direct : iterator_to_array($direct)) : [$direct];
        }

        $accountId = self::getAccountId($plan);
        if ($accountId && isset($context['contact_recordings'][$accountId])) {
            $items = $context['contact_recordings'][$accountId];
            return is_iterable($items) ? (is_array($items) ? $items : iterator_to_array($items)) : [$items];
        }

        if ($accountId && class_exists(ContactRecording::class)) {
            try {
                return ContactRecording::where('account_id', $accountId)->get()->all();
            } catch (\Throwable $e) {
                return [];
            }
        }

        return [];
    }

    /**
     * Retrieves ForRepossession record for an account.
     */
    protected static function getForRepossession(mixed $plan, ?array $context): mixed
    {
        $direct = self::getAttribute($plan, 'for_repossession') ??
                  self::getAttribute($plan, 'forRepossession');
        if ($direct !== null) {
            return $direct;
        }

        $accountId = self::getAccountId($plan);
        if ($accountId && isset($context['for_repossessions'][$accountId])) {
            $items = $context['for_repossessions'][$accountId];
            return $items instanceof Collection ? $items->first() : ($items[0] ?? null);
        }

        if ($accountId && class_exists(ForRepossession::class)) {
            try {
                return ForRepossession::where('account_id', $accountId)->first();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Retrieves RepossessionRequest records for an account.
     */
    protected static function getRepossessionRequests(mixed $plan, ?array $context): array
    {
        $direct = self::getAttribute($plan, 'repossession_requests') ??
                  self::getAttribute($plan, 'repossessionRequests') ??
                  self::getAttribute($plan, 'repossession_request');
        if ($direct !== null) {
            return is_iterable($direct) ? (is_array($direct) ? $direct : iterator_to_array($direct)) : [$direct];
        }

        $recordings = self::getContactRecordings($plan, $context);
        $requests = [];

        foreach ($recordings as $rec) {
            $recId = self::getAttribute($rec, 'id');
            if ($recId && isset($context['repossession_requests'][$recId])) {
                $reqs = $context['repossession_requests'][$recId];
                foreach ($reqs as $r) {
                    $requests[] = $r;
                }
            } elseif ($recId && class_exists(RepossessionRequest::class)) {
                try {
                    $dbReqs = RepossessionRequest::where('contact_recording_id', $recId)->get();
                    foreach ($dbReqs as $r) {
                        $requests[] = $r;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }

        return $requests;
    }

    /**
     * Calculates total payments made by an account.
     */
    protected static function getTotalPayments(mixed $plan, ?array $context): float
    {
        $directTotal = self::getAttribute($plan, 'total_paid') ??
                       self::getAttribute($plan, 'amount_paid') ??
                       self::getAttribute($plan, 'payment_amount');
        if ($directTotal !== null && is_numeric($directTotal)) {
            return (float) $directTotal;
        }

        $receipts = self::getAttribute($plan, 'receipt_encodings') ??
                    self::getAttribute($plan, 'receiptEncodings');
        if ($receipts !== null && is_iterable($receipts)) {
            $sum = 0.0;
            foreach ($receipts as $r) {
                $sum += (float) self::getAttribute($r, 'amount', 0);
            }
            return $sum;
        }

        $accountId = self::getAccountId($plan);
        if ($accountId && isset($context['receipts'][$accountId])) {
            $sum = 0.0;
            foreach ($context['receipts'][$accountId] as $r) {
                $sum += (float) self::getAttribute($r, 'amount', 0);
            }
            return $sum;
        }

        if ($accountId && class_exists(ReceiptEncoding::class)) {
            try {
                return (float) ReceiptEncoding::where('account_id', $accountId)->sum('amount');
            } catch (\Throwable $e) {
                return 0.0;
            }
        }

        return 0.0;
    }

    /**
     * Checks if an account has any contact recording.
     */
    public static function hasContactRecording(mixed $plan, ?array $context = null): bool
    {
        $recordings = self::getContactRecordings($plan, $context);
        return !empty($recordings);
    }

    /**
     * Helper to safely get an account ID from model, object, or array.
     */
    protected static function getAccountId(mixed $plan): ?int
    {
        $id = self::getAttribute($plan, 'id');
        if ($id !== null && is_numeric($id)) {
            return (int) $id;
        }
        $accId = self::getAttribute($plan, 'account_id');
        if ($accId !== null && is_numeric($accId)) {
            return (int) $accId;
        }
        return null;
    }

    /**
     * Helper to extract attributes from Array, Model, or stdClass.
     */
    public static function getAttribute(mixed $item, string $key, mixed $default = null): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }
        if (is_object($item)) {
            if ($item instanceof \Illuminate\Database\Eloquent\Model) {
                return $item->getAttribute($key) ?? $default;
            }
            return $item->{$key} ?? $default;
        }
        return $default;
    }

    /**
     * Parses non-payments ('UPDATED', 'LAST PAID 1 MONTH AGO', '1', '2', '3', etc.) into an integer.
     */
    public static function parseNonPayments(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (empty($value)) {
            return 0;
        }

        $val = strtoupper(trim((string) $value));
        if ($val === 'UPDATED') {
            return 0;
        }

        if (preg_match('/^NP\s*(\d+)$/i', $val, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/LAST\s+PAID\s+(\d+)\s+MONTH/i', $val, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)/', $val, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Parses days past due from integer days_past_due or string dpd_bucket.
     */
    public static function parseDaysPastDue(mixed $item): int
    {
        $dpd = self::getAttribute($item, 'days_past_due');
        if ($dpd !== null && is_numeric($dpd)) {
            return (int) $dpd;
        }

        $bucket = self::getAttribute($item, 'dpd_bucket');
        if (!empty($bucket) && preg_match('/DPD\s*(\d+)/i', (string) $bucket, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Checks if a date falls within the current month or within the last 30 days.
     */
    public static function isWithinMonth(?string $dateStr): bool
    {
        if (empty($dateStr)) {
            return false;
        }
        try {
            $date = Carbon::parse($dateStr);
            $now  = Carbon::now();
            return ($date->year === $now->year && $date->month === $now->month)
                || ($date->diffInDays($now) <= 30 && $date <= $now->copy()->addDays(31));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Checks if a contact recording contains a Promise-to-Pay (PTP) commitment.
     */
    protected static function hasPtpCommitment(mixed $recording): bool
    {
        $fields = [
            self::getAttribute($recording, 'action_taken'),
            self::getAttribute($recording, 'next_action_plan'),
            self::getAttribute($recording, 'remarks'),
            self::getAttribute($recording, 'follow_up_mode'),
        ];

        foreach ($fields as $field) {
            if (!empty($field) && is_string($field)) {
                if (preg_match('/\bptp\b|promise\s*to\s*pay|commitment/i', $field)) {
                    return true;
                }
            }
        }

        return false;
    }
}
