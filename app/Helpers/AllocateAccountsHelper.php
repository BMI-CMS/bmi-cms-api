<?php

namespace App\Helpers;

use App\Constants\Constants;

class AllocateAccountsHelper
{
    public static function accountsClassification(array $accounts)
    {
        $allocatedAccounts = [];
        foreach ($accounts as $account) {
            $dpdBucket = Constants::DPDBUCKET[$account["dpd_bucket"]] ?? 0;
            $variables = 1;

            if ($dpdBucket >= Constants::NP3) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_FORREPOSSESSION][] = $account;
                continue;
            }

            if ($account['is_force_prioritized']) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_FORCE_PRIORITIZED][] = $account;
                continue;
            }

            if (!$account['next_action_date']) {
                if ($account['payment_status'] === Constants::FIRSTDUEDATE || $account['payment_status'] === Constants::NONSTARTER) {
                    $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_FDD_NS][] = $account;
                    continue;
                }

                if ($dpdBucket === Constants::NP2) {
                    $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_PRIORITY_1_ACCOUNTS][] = $account;
                    continue;
                }

                if ($account["dpd_bucket"] === Constants::LESSTHAN1MONTHDUE) {
                    $allocatedAccounts[Constants::REGULAR][Constants::PROPERTY_DPD_1_30_DAYS][] = $account;
                    continue;
                }

                if ($dpdBucket === Constants::NP1) {
                    $allocatedAccounts[Constants::REGULAR][Constants::PROPERTY_NP_1][] = $account;
                    continue;
                }

                $allocatedAccounts[Constants::REGULAR][Constants::PROPERTY_NP_0][] = $account;
                continue;
            }

            if ($account['next_action_plan'] === Constants::PROMISETOPAY) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_PROMISE_TO_PAY][] = $account;
                continue;
            }

            if ($account['next_action_plan'] === Constants::FORREPO) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_FOR_REPOSSESSION][] = $account;
                continue;
            }

            if ($account['next_action_plan'] === Constants::FORSKIPTRACE) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_FOR_SKIPTRACE][] = $account;
                continue;
            }

            if ($account['next_action_plan'] === Constants::FORCEDPRIORITIZED) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_FORCE_PRIORITIZED][] = $account;
                continue;
            }

            if ($account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER1 || $account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER2) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_NOTICE_AND_DEMAND_LETTER][] = $account;
                continue;
            }

            if ($account['current_category']) {
                $previousCategory = Constants::PREVIOUSCLASSIFICATION[$account['current_category']];
                if (count($previousCategory) == 2) {
                    $allocatedAccounts[$previousCategory[0]][$previousCategory[1]][] = $account;
                } else if (count($previousCategory) == 3) {
                    $allocatedAccounts[$previousCategory[0]][$previousCategory[1]][$previousCategory[2]][] = $account;
                }
            }
        }
        return $allocatedAccounts;
    }

    public static function accountsClassificationPSGC(array $account): ?string
    {
        $dpdBucket = Constants::DPDBUCKET[$account['dpd_bucket']] ?? 0;

        if ($dpdBucket >= Constants::NP3) {
            return Constants::CLASSIFICATION_FOR_REPO;
        }

        if ($account['is_force_prioritized']) {
            return Constants::CLASSIFICATION_FORCE_PRIORITIZED;
        }

        if (!$account['next_action_date']) {
            if ($account['payment_status'] === Constants::FIRSTDUEDATE || $account['payment_status'] === Constants::NONSTARTER) {
                return Constants::CLASSIFICATION_FDD_NS;
            }

            if ($dpdBucket === Constants::NP2) {
                return Constants::CLASSIFICATION_PRIORITY_1;
            }

            if ($account['dpd_bucket'] === Constants::LESSTHAN1MONTHDUE) {
                return Constants::CLASSIFICATION_DPD_1_30;
            }

            if ($dpdBucket === Constants::NP1) {
                return Constants::CLASSIFICATION_NP_1;
            }

            return Constants::CLASSIFICATION_NP_0;
        }

        if ($account['next_action_plan'] === Constants::PROMISETOPAY) {
            return Constants::CLASSIFICATION_PROMISE_TO_PAY;
        }

        if ($account['next_action_plan'] === Constants::FORREPO) {
            return Constants::CLASSIFICATION_FOR_REPO;
        }

        if ($account['next_action_plan'] === Constants::FORSKIPTRACE) {
            return Constants::CLASSIFICATION_FOR_SKIPTRACE;
        }

        if ($account['next_action_plan'] === Constants::FORCEDPRIORITIZED) {
            return Constants::CLASSIFICATION_FORCE_PRIORITIZED;
        }

        if ($account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER1 || $account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER2) {
            return Constants::CLASSIFICATION_NOTICE_AND_DEMAND_LETTER;
        }

        if ($account['current_category']) {
            return $account['current_category'];
        }

        return '';
    }
}
