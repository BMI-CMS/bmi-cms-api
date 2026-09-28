<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            FollowUpModeSeeder::class,
            CompanySeeder::class,
            CompanyFollowUpModeSeeder::class,
            ReasonForDefaultSeeder::class,
            CompanyReasonForDefaultSeeder::class,
            ActionTakenTypesSeeder::class,
            CompanyActionTakenTypeSeeder::class,
            NextActionPlanSeeder::class,
            RoleSeeder::class,
            CompanyNextActionPlanSeeder::class,
            CmsUserSeeder::class,
            AccountSeeder::class,
            UserSeeder::class,
            ContactRecordingSeeder::class,
            RepossessionRequestSeeder::class,
            ForRepossessionSeeder::class,
            ReceiptEncodingSeeder::class,
            RestructuringSeeder::class,
            PromiseToPaySeeder::class,
            ContactInformationSeeder::class,
            CollectionAddressSeeder::class
        ]);
    }
}
