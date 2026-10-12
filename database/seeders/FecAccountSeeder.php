<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FecAccount;

class FecAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FecAccount::create([
            'account_number' => '81652348',
            'customer_id' => 120356482,
            'customer_name' => 'JOY DELA CRUZ',
            'monthly_amortization' => 5148.00,
            'past_due_balance' => 5123.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            // 'no_of_non_payments' => floor($now->parse('2026-09-25')->diffInMonths(now())),
            'no_of_non_payments' => 0,
            'outstanding_balance' => 26354.00,
            'last_payment_date' => '2026-04-04',
            'asset' => 0.00,
            'is_adverse_finding' => null,
            'form_status' => 'For Approval',
            'psgc_code' => '137404000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            //'created_at' => $now
        ]);

        FecAccount::create([
            'account_number' => '81652348',
            'customer_id' => 120356482,
            'customer_name' => 'JOEY DELA CRUZ',
            'monthly_amortization' => 5148.00,
            'past_due_balance' => 5123.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            // 'no_of_non_payments' => floor($now->parse('2026-09-25')->diffInMonths(now())),
            'no_of_non_payments' => 0,
            'outstanding_balance' => 26354.00,
            'last_payment_date' => '2026-04-04',
            'asset' => 0.00,
            'is_adverse_finding' => null,
            'form_status' => 'Approved',
            'psgc_code' => '137404000',
            'assigned_cc_id' => 2,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            //'created_at' => $now
        ]);

        FecAccount::create([
            'account_number' => '81652348',
            'customer_id' => 120356482,
            'customer_name' => 'HERSHEY DELA CRUZ',
            'monthly_amortization' => 5148.00,
            'past_due_balance' => 5123.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            // 'no_of_non_payments' => floor($now->parse('2026-09-25')->diffInMonths(now())),
            'no_of_non_payments' => 0,
            'outstanding_balance' => 26354.00,
            'last_payment_date' => '2026-04-04',
            'asset' => 0.00,
            'is_adverse_finding' => null,
            'form_status' => 'Rejected',
            'psgc_code' => '137404000',
            'assigned_cc_id' => 3,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            //'created_at' => $now
        ]);
    }
}
