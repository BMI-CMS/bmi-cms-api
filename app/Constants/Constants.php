<?php

namespace App\Constants;

class Constants
{
    // Account Period
    public const NP1 = 1;
    public const NP2 = 2;
    public const NP3 = 3;
    public const LESSTHAN1MONTHDUE = "DPD 1-30";
    public const DPDBUCKET = [
        "DPD 0" => 0,
        "DPD 1-30" => 0,
        "DPD 31-60" => 1,
        "DPD 61-90" => 2,
        "DPD 91-120" => 3,
        "DPD 121-150" => 4,
        "DPD 151-180" => 5,
        "DPD 181-210" => 6,
        "DPD 211-240" => 7,
        "DPD 241-270" => 8,
        "DPD 271-300" => 9,
        "DPD 301-330" => 10,
        "DPD 331-360" => 11,
        "DPD MORE THAN 360" => 12,
    ];

    public const PREVIOUSCLASSIFICATION = [
        'DPD 1-30 DAYS' => ['regular', 'dpd_1_30_days'],
        'NP0' => ['regular', 'np_0'],
        'NP1' => ['regular', 'np_1'],
        'PRIORITY 1 ACCCOUNTS' => ['prioritization', 'priority_1_accounts'],
        'FDD/NS' => ['prioritization', 'fdd_ns'],
        'FOR REPOSSESSION' => ['prioritization', 'for_repossession'],
        'PROMISE TO PAY' => ['prioritization', 'promise_to_pay'],

        'FOR SKIPTRACE' => ['prioritization', 'next_action_plan', 'for_skiptrace'],
        'FORCED PRIORITIZED' => ['prioritization', 'next_action_plan', 'force_prioritized'],
        'NOTICE AND DEMAND LETTER' => ['prioritization', 'next_action_plan', 'notice_and_demand_letter'],
    ];

    public const PROMISETOPAY = 'PROMISE TO PAY';
    public const FORREPO = 'FOR REPO';
    public const FORSKIPTRACE = 'FOR SKIPTRACE';
    public const FORCEDPRIORITIZED = 'forced prioritized';

    public const NOTICEANDDEMANDLETTER1 =  "FOR SERVICE OF COLLECTION NOTICE/ATTY'S LETTER";
    public const NOTICEANDDEMANDLETTER2 = 'FOR SERVICE OF COLLECTION NOTICE';
    public const PRIORITIZATION = 'prioritization';
    public const REGULAR = 'regular';
    public const NEXT_ACTION_PLAN = 'next_action_plan';
    public const PROPERTY_COLLECTED = 'collected';
    public const PROPERTY_FORREPOSSESSION = 'for_repossession';
    public const PROPERTY_FDD_NS = 'fdd_ns';
    public const PROPERTY_FORCE_PRIORITIZED = 'force_prioritized';
    public const PROPERTY_FOR_REPOSSESSION = 'for_repossession';
    public const PROPERTY_PRIORITY_1_ACCOUNTS = 'priority_1_accounts';
    public const PROPERTY_DPD_1_30_DAYS = 'dpd_1_30_days';
    public const PROPERTY_NP_1 = 'np_1';
    public const PROPERTY_NP_0 = 'np_0';
    public const PROPERTY_PROMISE_TO_PAY = 'promise_to_pay';
    public const PROPERTY_FOR_SKIPTRACE = 'for_skiptrace';
    public const PROPERTY_NOTICE_AND_DEMAND_LETTER = 'notice_and_demand_letter';

    public const CLASSIFICATION_FDD_NS = 'FDD/NS';
    public const CLASSIFICATION_FORCE_PRIORITIZED = 'Force Prioritized';
    public const CLASSIFICATION_FOR_REPO = 'For Repo';
    public const CLASSIFICATION_PRIORITY_1 = 'Priority 1 Accounts';
    public const CLASSIFICATION_DPD_1_30 = 'DPD 1-30 Days';
    public const CLASSIFICATION_NP_1 = 'NP1';
    public const CLASSIFICATION_NP_0 = 'NP0';
    public const CLASSIFICATION_CONTACT_RECORDING = 'Contact Recording';
    public const CLASSIFICATION_PROMISE_TO_PAY = 'Promise To Pay';
    public const CLASSIFICATION_FOR_SKIPTRACE = 'For Skiptrace';
    public const CLASSIFICATION_NOTICE_AND_DEMAND_LETTER = 'Notice and Demand Letter';



    public const TEAM_ACCOUNTS_PER_PAGE = 15;
    public const TEAM_ACCOUNTS_MAX_PER_PAGE = 50;
    public const FIRSTDUEDATE = 'FIRST DUE DATE';
    public const NONSTARTER = 'NON-STARTER';
}
