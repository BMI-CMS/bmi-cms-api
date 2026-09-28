<?php

namespace App\Repositories;

use App\Models\Account;
use App\Queries\ReceiptEncodingQuery;
use App\Queries\RestructuringQuery;
use App\Queries\ForRepossessionQuery;
use App\Queries\ContactRecordingQuery;
use Carbon\Carbon;

class DashboardRepository
{
    public function getAccountPerformance(int $userId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $receiptEncodings = ReceiptEncodingQuery::receiptEncoding(
            $startDate,
            $endDate
        );

        $restructurings = RestructuringQuery::restructured(
            $startDate,
            $endDate
        );

        $forRepossessions = ForRepossessionQuery::forRepossessed(
            $startDate,
            $endDate
        );

        return Account::leftJoinSub(
            $receiptEncodings,
            'receipt_encodings',
            'accounts.id',
            '=',
            'receipt_encodings.account_id'
        )
            ->leftJoinSub(
                $restructurings,
                'restructurings',
                'accounts.id',
                '=',
                'restructurings.account_id'
            )
            ->leftJoinSub(
                $forRepossessions,
                'for_repossessions',
                'accounts.id',
                '=',
                'for_repossessions.account_id'
            )
            ->selectRaw('
            COUNT(accounts.id) AS total_accounts,
            COUNT(receipt_encodings.account_id) AS total_paid_accounts,
            COUNT(for_repossessions.account_id) AS total_repossessed_accounts,
            COUNT(restructurings.account_id) AS total_restructured_accounts,

            SUM(accounts.past_due_balance) AS total_past_due_amount,
            SUM(receipt_encodings.amount) AS total_receipt_encode_amount,
            SUM(restructurings.new_monthly_amortization) AS total_restructured_amount,
            SUM(for_repossessions.amount) AS total_repossessed_amount
    ')
            ->where('accounts.assigned_cc_id', $userId)
            ->where('accounts.created_at', '>=', $startDate)
            ->where('accounts.created_at', '<', $endDate)
            ->first();
    }

    public function getDailyAccounts(int $userId)
    {
        $startDate = Carbon::today()->addDay();

        $contactRecording = ContactRecordingQuery::contactRecordedDaily(
            $startDate
        );

        return Account::leftJoinSub(
            $contactRecording,
            'contact_recordings',
            'accounts.id',
            '=',
            'contact_recordings.account_id'
        )
            ->leftJoin(
                'collecting_addresses',
                'accounts.id',
                '=',
                'collecting_addresses.account_id'
            )
            ->leftJoin(
                'contact_information',
                'accounts.id',
                '=',
                'contact_information.account_id'
            )
            ->select([
                'accounts.id',
                'contact_recordings.contact_recording_id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.monthly_amortization',
                'accounts.past_due_balance',
                'accounts.days_past_due',
                'accounts.dpd_bucket',
                'accounts.no_of_non_payments',
                'accounts.outstanding_balance',
                'accounts.last_payment_date',
                'accounts.asset',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'accounts.assigned_cc_id',
                'accounts.assigned_ch_id',
                'accounts.assigned_am_id',
                'accounts.assigned_dh_id',
                'accounts.assigned_date',
                'accounts.follow_up_date',
                'collecting_addresses.psgc_code',
                'collecting_addresses.unit_lot_block',
                'collecting_addresses.street_name',
                'collecting_addresses.subdivision_village',
                'collecting_addresses.province',
                'collecting_addresses.postal_code',
                'collecting_addresses.barangay',
                'collecting_addresses.city_municipality',
                'collecting_addresses.region',
                'contact_information.contact_number',
                'contact_information.email',
                'contact_information.social_media_account',
                'accounts.created_at',
                'contact_recordings.next_action_date'
            ])
            ->where('accounts.assigned_cc_id', $userId)
            ->where('accounts.created_at', '<', $startDate)
            ->orderBy('accounts.id', 'asc')
            ->orderBy('contact_recordings.next_action_date', 'desc')
            ->get();
    }

    public function getMonthlyAccounts(int $userId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonth()->startOfMonth();

        $contactRecording = ContactRecordingQuery::contactRecordedMonthly(
            $startDate,
            $endDate
        );

        return Account::leftJoinSub(
            $contactRecording,
            'contact_recordings',
            'accounts.id',
            '=',
            'contact_recordings.account_id'
        )
            ->leftJoin(
                'collecting_addresses',
                'accounts.id',
                '=',
                'collecting_addresses.account_id'
            )
            ->leftJoin(
                'contact_information',
                'accounts.id',
                '=',
                'contact_information.account_id'
            )
            ->select([
                'accounts.id',
                'contact_recordings.contact_recording_id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.monthly_amortization',
                'accounts.past_due_balance',
                'accounts.days_past_due',
                'accounts.dpd_bucket',
                'accounts.no_of_non_payments',
                'accounts.outstanding_balance',
                'accounts.last_payment_date',
                'accounts.asset',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'accounts.assigned_cc_id',
                'accounts.assigned_ch_id',
                'accounts.assigned_am_id',
                'accounts.assigned_dh_id',
                'accounts.assigned_date',
                'accounts.follow_up_date',
                'collecting_addresses.psgc_code',
                'collecting_addresses.unit_lot_block',
                'collecting_addresses.street_name',
                'collecting_addresses.subdivision_village',
                'collecting_addresses.province',
                'collecting_addresses.postal_code',
                'collecting_addresses.barangay',
                'collecting_addresses.city_municipality',
                'collecting_addresses.region',
                'contact_information.contact_number',
                'contact_information.email',
                'contact_information.social_media_account',
                'accounts.created_at',
                'contact_recordings.next_action_date'
            ])
            ->where('accounts.assigned_cc_id', $userId)
            ->where('accounts.created_at', '>=', $startDate)
            ->where('accounts.created_at', '<', $endDate)
            ->orderBy('accounts.id', 'asc')
            ->orderBy('accounts.created_at', 'desc')
            ->get();
    }
}
