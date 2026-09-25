<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            'account_number' => '81652348',
            'customer_id' => 120356482,
            'customer_name' => 'RACHEL DELA CRUZ',
            'monthly_amortization' => 5148.00,
            'past_due_balance' => 5123.00,
            'days_past_due' => 5,
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' => 'LAST PAID 1 MONTH AGO',
            'outstanding_balance' => 26354.00,
            'last_payment_date' => '2026-04-04',
            'asset' => 0.00,
            'psgc_code' => '1234567890',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'ADDRESS 1',
            'email' => 'RACHEL@gmail.com',
            'social_media_account' => 'RACHEL/tiktok',
            'contact_number' => '9703361111'
        ]);

        Account::create([
            'account_number' => '81987654',
            'customer_id' => 1232654825,
            'customer_name' => 'MARIFE CASTILLO',
            'monthly_amortization' => 6790.00,
            'past_due_balance' => 13458.00,
            'days_past_due' => 25,
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' => 'LAST PAID 1 MONTH AGO',
            'outstanding_balance' => 240523.00,
            'last_payment_date' => '2026-04-06',
            'asset' => 0.00,
            'psgc_code' => '1234567890',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'ADDRESS 1',
            'email' => 'MARIFE@gmail.com',
            'social_media_account' => 'MARIFE/tiktok',
            'contact_number' => '09703361111'
        ]);

        Account::create([
            'account_number' => '77325643',
            'customer_id' => 1932654825,
            'customer_name' => 'JOSEPH SALUDES',
            'monthly_amortization' => 3423.00,
            'past_due_balance' => 6784.00,
            'days_past_due' => 2,
            'dpd_bucket' => 'DPD 271-300',
            'no_of_non_payments' => 'LAST PAID 1 MONTH AGO',
            'outstanding_balance' => 6465.00,
            'last_payment_date' => '2025-10-02',
            'asset' => 0.00,
            'psgc_code' => '1234567890',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'ADDRESS 1',
            'email' => 'JOSEPH@gmail.com',
            'social_media_account' => 'JOSEPH/tiktok',
            'contact_number' => '09703361111'
        ]);





        //
        Account::create([
            'account_number' => '81356487',
            'customer_id' => 132658452,
            'customer_name' => 'CHRISTOPER DE LEON',
            'monthly_amortization' => 4536.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 25,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 58745.00,
            'last_payment_date' => '2026-04-22',
            'asset' => 0.00,
            'psgc_code' => '1234567890',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'ADDRESS 1',
            'email' => 'CHRISTOPER@gmail.com',
            'social_media_account' => 'JOSEPH/tiktok',
            'contact_number' => '09703361111'
        ]);


        //
        Account::create([
            'account_number' => '81356501',
            'customer_id' => 132658501,
            'customer_name' => 'MARIA CLARA',
            'monthly_amortization' => 3200.00,
            'past_due_balance' => 6400.00,
            'days_past_due' => 60,
            'dpd_bucket' => 'DPD 60',
            'no_of_non_payments' => '2',
            'outstanding_balance' => 45000.00,
            'last_payment_date' => '2026-07-15',
            'asset' => 0.00,
            'psgc_code' => '137404000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'QUEZON CITY, METRO MANILA',
            'email' => 'm.clara@yahoo.com',
            'social_media_account' => 'MARIAC/facebook',
            'contact_number' => '09171234501'
        ]);

        Account::create([
            'account_number' => '81356502',
            'customer_id' => 132658502,
            'customer_name' => 'JUAN DELA CRUZ',
            'monthly_amortization' => 15000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 350000.00,
            'last_payment_date' => '2026-09-01',
            'asset' => 0.00,
            'psgc_code' => '137604000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'MAKATI CITY, METRO MANILA',
            'email' => 'juandelacruz@gmail.com',
            'social_media_account' => 'JUANDC/instagram',
            'contact_number' => '09181234502'
        ]);

        Account::create([
            'account_number' => '81356503',
            'customer_id' => 132658503,
            'customer_name' => 'PEDRO PENDUKO',
            'monthly_amortization' => 2100.50,
            'past_due_balance' => 6301.50,
            'days_past_due' => 90,
            'dpd_bucket' => 'DPD 90',
            'no_of_non_payments' => '3',
            'outstanding_balance' => 18000.00,
            'last_payment_date' => '2026-06-10',
            'asset' => 0.00,
            'psgc_code' => '031400000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-26',
            'collecting_address' => 'MALOLOS, BULACAN',
            'email' => 'pedro.p@hotmail.com',
            'social_media_account' => 'PEDROP/tiktok',
            'contact_number' => '09221234503'
        ]);

        Account::create([
            'account_number' => '81356504',
            'customer_id' => 132658504,
            'customer_name' => 'ANA ROCES',
            'monthly_amortization' => 8400.00,
            'past_due_balance' => 8400.00,
            'days_past_due' => 30,
            'dpd_bucket' => 'DPD 30',
            'no_of_non_payments' => '1',
            'outstanding_balance' => 105000.00,
            'last_payment_date' => '2026-08-20',
            'asset' => 0.00,
            'psgc_code' => '043404000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-28',
            'collecting_address' => 'SAN PABLO, LAGUNA',
            'email' => 'ana.roces@gmail.com',
            'social_media_account' => 'ANAROCES/facebook',
            'contact_number' => '09331234504'
        ]);

        Account::create([
            'account_number' => '81356505',
            'customer_id' => 132658505,
            'customer_name' => 'JOSE RIZAL',
            'monthly_amortization' => 5000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 50000.00,
            'last_payment_date' => '2026-09-15',
            'asset' => 0.00,
            'psgc_code' => '043400000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-30',
            'collecting_address' => 'CALAMBA, LAGUNA',
            'email' => 'j.rizal@yahoo.com',
            'social_media_account' => 'JRIZAL/twitter',
            'contact_number' => '09441234505'
        ]);

        Account::create([
            'account_number' => '81356506',
            'customer_id' => 132658506,
            'customer_name' => 'LIZA SOBERANO',
            'monthly_amortization' => 12500.00,
            'past_due_balance' => 37500.00,
            'days_past_due' => 120,
            'dpd_bucket' => 'DPD 120',
            'no_of_non_payments' => '4',
            'outstanding_balance' => 250000.00,
            'last_payment_date' => '2026-05-10',
            'asset' => 0.00,
            'psgc_code' => '137401000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-24',
            'collecting_address' => 'TAGUIG CITY, METRO MANILA',
            'email' => 'liza.s@gmail.com',
            'social_media_account' => 'LIZAS/instagram',
            'contact_number' => '09551234506'
        ]);

        Account::create([
            'account_number' => '81356507',
            'customer_id' => 132658507,
            'customer_name' => 'VIC SOTTO',
            'monthly_amortization' => 6000.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 72000.00,
            'last_payment_date' => '2026-09-10',
            'asset' => 0.00,
            'psgc_code' => '137402000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-29',
            'collecting_address' => 'PASIG CITY, METRO MANILA',
            'email' => 'bossing@gmail.com',
            'social_media_account' => 'VIC/facebook',
            'contact_number' => '09661234507'
        ]);

        Account::create([
            'account_number' => '81356508',
            'customer_id' => 132658508,
            'customer_name' => 'SARAH GERONIMO',
            'monthly_amortization' => 9500.00,
            'past_due_balance' => 19000.00,
            'days_past_due' => 60,
            'dpd_bucket' => 'DPD 60',
            'no_of_non_payments' => '2',
            'outstanding_balance' => 114000.00,
            'last_payment_date' => '2026-07-22',
            'asset' => 0.00,
            'psgc_code' => '137403000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-26',
            'collecting_address' => 'MANDALUYONG CITY, METRO MANILA',
            'email' => 'sarah.g@yahoo.com',
            'social_media_account' => 'SARAHG/tiktok',
            'contact_number' => '09771234508'
        ]);

        Account::create([
            'account_number' => '81356509',
            'customer_id' => 132658509,
            'customer_name' => 'COCO MARTIN',
            'monthly_amortization' => 4500.00,
            'past_due_balance' => 4500.00,
            'days_past_due' => 30,
            'dpd_bucket' => 'DPD 30',
            'no_of_non_payments' => '1',
            'outstanding_balance' => 22500.00,
            'last_payment_date' => '2026-08-15',
            'asset' => 0.00,
            'psgc_code' => '137405000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-25',
            'collecting_address' => 'SAN JUAN CITY, METRO MANILA',
            'email' => 'tanggol@gmail.com',
            'social_media_account' => 'COCO/facebook',
            'contact_number' => '09881234509'
        ]);

        Account::create([
            'account_number' => '81356510',
            'customer_id' => 132658510,
            'customer_name' => 'KATHRYN BERNARDO',
            'monthly_amortization' => 7800.00,
            'past_due_balance' => 0.00,
            'days_past_due' => 0,
            'dpd_bucket' => 'DPD 0',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 156000.00,
            'last_payment_date' => '2026-09-20',
            'asset' => 0.00,
            'psgc_code' => '137404000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_id' => 7,
            'assigned_dh_id' => 8,
            'assigned_date' => '2026-09-18',
            'follow_up_date' => '2026-09-27',
            'collecting_address' => 'QUEZON CITY, METRO MANILA',
            'email' => 'kathryn.b@hotmail.com',
            'social_media_account' => 'KATHRYN/instagram',
            'contact_number' => '09991234510'
        ]);
    }
}
