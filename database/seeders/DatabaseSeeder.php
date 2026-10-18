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
            //FollowUpModeSeeder::class,
            CompanySeeder::class,
            // CompanyFollowUpModeSeeder::class,
            //  ReasonForDefaultSeeder::class,
            //  CompanyReasonForDefaultSeeder::class,
            //  ActionTakenTypesSeeder::class,
            //  CompanyActionTakenTypeSeeder::class,
            //  NextActionPlanSeeder::class,
            RoleSeeder::class,
            //   CompanyNextActionPlanSeeder::class,
            CmsUserSeeder::class,
            AccountClassificationRuleSeeder::class,
            UserSeeder::class,

            AccountSeeder::class,
            ContactRecordingSeeder::class,

            //! New Seeders, needs rework
            // AccountSeederV3::class,
            // ContactRecordingSeederV2::class,

            //  RepossessionRequestSeeder::class,
            //  ForRepossessionSeeder::class,
            // RestructuringSeeder::class,
            //PromiseToPaySeeder::class,
            CustomerDetailSeeder::class,
            CollectionAddressSeeder::class,
            // NonStarterPaymentSeeder::class,
            // FecAccountSeeder::class,
            // FecAccountVisitSeeder::class,
            // FecCustomerEvaluationSeeder::class,
            // CollectionSeeder::class,
            // ReceiptEncodingSeeder::class,
            // DepositEncodingSeeder::class
        ]);
    }
}
