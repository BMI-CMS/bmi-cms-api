<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Models\ContactInformation;

class ContactInformationSeeder extends Seeder
{
    /**
     * Creates one contact information row per account, using Faker data.
     * Seeded from the account number so reruns produce the same values.
     */
    public function run(): void
    {
        foreach (Account::all() as $account) {
            $faker = fake('en_PH');
            $faker->seed((int) $account->account_number);

            $name = $faker->firstName() . ' ' . $faker->lastName();

            ContactInformation::updateOrCreate(
                ['account_id' => $account->id],
                [
                    'contact_number' => $faker->numerify('09#########'),
                    'email' => Str::slug($name, '.') . '@example.com',
                    'social_media_account' => Str::slug($name, ''),
                ],
            );
        }
    }
}
