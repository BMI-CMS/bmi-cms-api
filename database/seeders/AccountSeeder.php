<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use Carbon\Carbon;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        //NP0 1 
        Account::create([
            'account_number' => '81652348',
            'customer_id' => 120356482,
            'customer_name' => 'RACHEL DELA CRUZ',
            'monthly_amortization' => 5148.00,
            'past_due_balance' => 5123.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            // 'no_of_non_payments' => floor($now->parse('2026-09-25')->diffInMonths(now())),
            'no_of_non_payments' => 0,
            'outstanding_balance' => 26354.00,
            'last_payment_date' => '2026-04-04',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);
        //DPD 1-30 2
        Account::create([
            'account_number' => '81987654',
            'customer_id' => 1232654825,
            'customer_name' => 'MARIFE CASTILLO',
            'monthly_amortization' => 6790.00,
            'past_due_balance' => 13458.00,
            'days_past_due' => 2,
            'dpd_bucket' => 'DPD 1-30',
            // 'no_of_non_payments' => floor($now->parse('2026-08-26')->diffInMonths(now())),
            'no_of_non_payments' => 0,
            'outstanding_balance' => 240523.00,
            'last_payment_date' => '2026-04-06',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);
        //NP1  3
        Account::create([
            'account_number' => '77325643',
            'customer_id' => 1932654825,
            'customer_name' => 'JOSEPH SALUDES',
            'monthly_amortization' => 3423.00,
            'past_due_balance' => 6784.00,
            'days_past_due' => 33,
            'dpd_bucket' => 'DPD 31-60',
            // 'no_of_non_payments' => floor($now->parse('2026-09-28')->diffInMonths(now())),
            'no_of_non_payments' => 1,
            'outstanding_balance' => 6465.00,
            'last_payment_date' => '2025-10-02',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);
        //skip trace 4
        Account::create([
            'account_number' => '81356487',
            'customer_id' => 132658452,
            'customer_name' => 'CHRISTOPER DE LEON',
            'monthly_amortization' => 4536.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 25,
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' => floor($now->parse($now)->diffInMonths(now())),
            'outstanding_balance' => 58745.00,
            'last_payment_date' => '2026-04-22',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now->copy()->subDays(),
        ]);
        //Notice & Demand Letter 5
        Account::create([
            'account_number' => '81356501',
            'customer_id' => 132658501,
            'customer_name' => 'MARIA CLARA',
            'monthly_amortization' => 3200.00,
            'past_due_balance' => 6400.00,
            'days_past_due' => 60,
            'dpd_bucket' => 'DPD 31-60',
            'no_of_non_payments' => floor($now->parse('2026-07-15')->diffInMonths(now())),
            'outstanding_balance' => 45000.00,
            'last_payment_date' => '2026-07-15',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);

        //Force Priority 6
        Account::create([
            'account_number' => '81356502',
            'customer_id' => 132658502,
            'customer_name' => 'JUAN DELA CRUZ',
            'monthly_amortization' => 15000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => floor($now->parse('2026-09-01')->diffInMonths(now())),
            'outstanding_balance' => 350000.00,
            'last_payment_date' => '2026-09-01',
            'asset' => 0.00,
            'is_force_prioritized' => true,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);

        //Promise to Pay 7
        Account::create([
            'account_number' => '81356503',
            'customer_id' => 132658503,
            'customer_name' => 'PEDRO PENDUKO',
            'monthly_amortization' => 2100.50,
            'past_due_balance' => 6301.50,
            'days_past_due' => 90,
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' => 2,
            'outstanding_balance' => 18000.00,
            'last_payment_date' => '2026-06-10',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-26',
            'created_at' => $now->copy()->subDays(1)
        ]);
        //For repo 8
        Account::create([
            'account_number' => '81356504',
            'customer_id' => 132658504,
            'customer_name' => 'ANA ROCES',
            'monthly_amortization' => 8400.00,
            'past_due_balance' => 8400.00,
            'days_past_due' => 92,
            'dpd_bucket' => 'DPD 91-120',
            'no_of_non_payments' => 3,
            'outstanding_balance' => 105000.00,
            'last_payment_date' => '2026-08-20',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-28',
            'created_at' => $now
        ]);
        //9
        Account::create([
            'account_number' => '81356505',
            'customer_id' => 132658505,
            'customer_name' => 'JOSE RIZAL',
            'monthly_amortization' => 5000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 90,
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' => 2,
            'outstanding_balance' => 50000.00,
            'last_payment_date' => '2026-09-15',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-30',
            'created_at' => $now
        ]);

        // Priority 1 Accounts 10 
        Account::create([
            'account_number' => '81356506',
            'customer_id' => 132658506,
            'customer_name' => 'LIZA SOBERANO',
            'monthly_amortization' => 12500.00,
            'past_due_balance' => 37500.00,
            'days_past_due' => 62,
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' => 2,
            'outstanding_balance' => 250000.00,
            'last_payment_date' => '2026-05-10',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-24',
            'created_at' => $now->copy()->subDays(4)
        ]);
        //FDD / NS 11
        Account::create([
            'account_number' => '81356507',
            'customer_id' => 132658507,
            'customer_name' => 'VIC SOTTO',
            'monthly_amortization' => 6000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' =>  floor($now->parse('2026-09-10')->diffInMonths(now())),
            'outstanding_balance' => 72000.00,
            'last_payment_date' => '2026-09-10',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-29',
            'created_at' => $now->copy()->subDays(1)
        ]);

        Account::create([
            'account_number' => '81356508',
            'customer_id' => 132658508,
            'customer_name' => 'SARAH GERONIMO',
            'monthly_amortization' => 9500.00,
            'past_due_balance' => 19000.00,
            'days_past_due' => 60,
            'dpd_bucket' => 'DPD 31-60',
            'no_of_non_payments' => floor($now->parse('2026-07-22')->diffInMonths(now())),
            'outstanding_balance' => 114000.00,
            'last_payment_date' => '2026-07-22',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-26',
            'created_at' => $now->copy()->addDays()
        ]);

        Account::create([
            'account_number' => '81356509',
            'customer_id' => 132658509,
            'customer_name' => 'COCO MARTIN',
            'monthly_amortization' => 4500.00,
            'past_due_balance' => 4500.00,
            'days_past_due' => 30,
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' =>  floor($now->parse('2026-08-15')->diffInMonths(now())),
            'outstanding_balance' => 22500.00,
            'last_payment_date' => '2026-08-15',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'created_at' => $now
        ]);

        Account::create([
            'account_number' => '81356510',
            'customer_id' => 132658510,
            'customer_name' => 'KATHRYN BERNARDO',
            'monthly_amortization' => 7800.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' =>  floor($now->parse('2026-09-20')->diffInMonths(now())),
            'outstanding_balance' => 156000.00,
            'last_payment_date' => '2026-09-20',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-27',
            'created_at' => $now->copy()->addDays()
        ]);


        Account::create([
            'account_number' => '81356511',
            'customer_id' => 132658511,
            'customer_name' => 'JOY DELA CRUZ',
            'monthly_amortization' => 8500.00,
            'past_due_balance' => 17000.00,
            'days_past_due' => 25,
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' => floor($now->parse('2026-08-25')->diffInMonths(now())),
            'outstanding_balance' => 145000.00,
            'last_payment_date' => '2026-08-25',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-28',
            'created_at' =>  $now,
        ]);

        Account::create([
            'account_number' => '81356512',
            'customer_id' => 132658512,
            'customer_name' => 'MARIA SANTOS',
            'monthly_amortization' => 12000.00,
            'past_due_balance' => 36000.00,
            'days_past_due' => 65,
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' =>  floor($now->parse('2026-07-20')->diffInMonths(now())),
            'outstanding_balance' => 320000.00,
            'last_payment_date' => '2026-07-20',
            'asset' => 0.00,
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-19',
            'follow_up_date' => '2026-09-29',
            'created_at' =>  $now,
        ]);

        Account::create([
            'account_number' => '81356513',
            'customer_id' => 132658513,
            'customer_name' => 'PEDRO REYES',
            'monthly_amortization' => 15000.00,
            'past_due_balance' => 60000.00,
            'days_past_due' => 95,
            'dpd_bucket' => 'DPD 91-120',
            'no_of_non_payments' => floor($now->parse('2026-06-15')->diffInMonths(now())),
            'outstanding_balance' => 450000.00,
            'last_payment_date' => '2026-06-15',
            'asset' => 25000.00,
            'assigned_cc_id' => 2,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-20',
            'follow_up_date' => '2026-09-30',
            'created_at' =>  $now,
        ]);

        Account::create([
            'account_number' => '81356514',
            'customer_id' => 132658514,
            'customer_name' => 'ANA GARCIA',
            'monthly_amortization' => 9500.00,
            'past_due_balance' => 19000.00,
            'days_past_due' => 91,
            'dpd_bucket' => 'DPD 31-60',
            'no_of_non_payments' => floor($now->parse('2026-08-05')->diffInMonths(now())),
            'outstanding_balance' => 210000.00,
            'last_payment_date' => '2026-08-05',
            'asset' => 0.00,
            'assigned_cc_id' => 2,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-21',
            'follow_up_date' => '2026-10-01',
            'created_at' =>  $now,
        ]);
    }
}
