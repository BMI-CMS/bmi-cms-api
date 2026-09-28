<?php

namespace App\Helpers;

use App\Constants\Constants;
use App\Models\Account;

class AllocateAccountsHelper
{
    public static function accountsAllocation(array $accounts)
    {
        $allocatedAccounts = [];
        foreach ($accounts as $account) {

            if ($account['non_starter_payment_id']) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_FDD_NS][] = $account;
                continue;
            }

            if (!$account['next_action_date']) {

                if ($account['is_force_prioritized']) {
                    $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_FORCE_PRIORITIZED][] = $account;
                    continue;
                }

                if (
                    $account['no_of_non_payments'] >= Constants::NP3 &&
                    $account['days_past_due'] >= Constants::DPD91
                ) {
                    $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_FOR_REPOSSESSION][] = $account;
                    continue;
                }

                if ($account['no_of_non_payments'] >= Constants::NP2) {
                    $allocatedAccounts[Constants::PRIORITIZATION][Constants::PROPERTY_PRIORITY_1_ACCOUNTS][] = $account;
                    continue;
                }

                if (
                    $account['days_past_due'] > 0 &&
                    $account['days_past_due'] < 30
                ) {
                    $allocatedAccounts[Constants::REGULAR][Constants::PROPERTY_DPD_1_30_DAYS][] = $account;
                    continue;
                }

                if ($account['no_of_non_payments'] >= Constants::NP1) {
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

            if ($account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER) {
                $allocatedAccounts[Constants::PRIORITIZATION][Constants::NEXT_ACTION_PLAN][Constants::PROPERTY_NOTICE_AND_DEMAND_LETTER][] = $account;
            }
        }

        return $allocatedAccounts;
    }

    public static function PSGCAccountsAllocation(Account $account): ?string
    {
        if ($account['non_starter_payment_id']) {
            return Constants::CLASSIFICATION_FDD_NS;
        }

        if (!$account['next_action_date']) {

            if ($account['is_force_prioritized']) {
                return Constants::CLASSIFICATION_FORCE_PRIORITIZED;
            }

            if (
                $account['no_of_non_payments'] >= Constants::NP3 &&
                $account['days_past_due'] >= Constants::DPD91
            ) {
                return Constants::CLASSIFICATION_FOR_REPO;
            }

            if ($account['no_of_non_payments'] >= Constants::NP2) {
                return Constants::CLASSIFICATION_PRIORITY_1;
            }

            if (
                $account['days_past_due'] > 0 &&
                $account['days_past_due'] < 30
            ) {
                return Constants::CLASSIFICATION_DPD_1_30;
            }

            if ($account['no_of_non_payments'] >= Constants::NP1) {
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

        if ($account['next_action_plan'] === Constants::NOTICEANDDEMANDLETTER) {
            return Constants::CLASSIFICATION_NOTICE_AND_DEMAND_LETTER;
        }

        return '';
    }
}
