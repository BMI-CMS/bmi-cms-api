<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Models\CustomerDetail;

class CustomerDetailsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Account::all() as $account) {
            $faker = fake('en_PH');
            $faker->seed((int) $account->account_number);

            $name = $faker->firstName() . ' ' . $faker->lastName();

            CustomerDetail::updateOrCreate(
                ['account_id' => $account->id],
                [
                    'contact_number' => $faker->numerify('09#########'),
                    'email' => Str::slug($name, '.') . '@example.com',
                    'social_media_account' => Str::slug($name, '')
                ],
            );
        }
    }
}
