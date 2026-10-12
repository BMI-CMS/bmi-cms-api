<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\CustomerDetail;

/**
 * Sample contact details for every account (filler data derived from the account number).
 * Safe to rerun: details are matched by account_id.
 */
class CustomerDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Account::query()->orderBy('id')->get(['id', 'account_number']) as $account) {
            CustomerDetail::updateOrCreate(
                ['account_id' => $account->id],
                [
                    'contact_number' => '0917'.str_pad((string) $account->id, 7, '0', STR_PAD_LEFT),
                    'email' => "customer.{$account->account_number}@example.com",
                    'social_media_account' => "customer.{$account->account_number}",
                ],
            );
        }
    }
}
