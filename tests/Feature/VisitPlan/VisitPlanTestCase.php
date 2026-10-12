<?php

namespace Tests\Feature\VisitPlan;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AccountClassificationRuleSeeder;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CmsUserSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ContactRecordingSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class VisitPlanTestCase extends TestCase
{
    // Truncation (not transactions) resets auto-increment ids, which the existing seeders hard-code.
    use DatabaseTruncation;

    protected const API_KEY = 'visit-plan-test-key';

    protected const CREDIT_COUNSELOR_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.api.key' => self::API_KEY]);
    }

    /**
     * Freeze "now" at a wall-clock time in the application timezone (Asia/Manila).
     */
    protected function travelToManila(string $dateTime): Carbon
    {
        $now = Carbon::parse($dateTime);
        $this->travelTo($now);

        return $now;
    }

    protected function seedVisitPlanData(): void
    {
        $this->seed([
            CompanySeeder::class,
            RoleSeeder::class,
            CmsUserSeeder::class,
            UserSeeder::class,
            AccountSeeder::class,
            ContactRecordingSeeder::class,
            AccountClassificationRuleSeeder::class,
        ]);
    }

    protected function getDailyVisitPlan(): TestResponse
    {
        return $this->getVisitPlan('daily');
    }

    protected function getVisitPlan(string $period): TestResponse
    {
        Sanctum::actingAs(User::where('cms_user_id', self::CREDIT_COUNSELOR_ID)->firstOrFail());

        return $this->withHeaders(['BMI_CMS_KEY' => self::API_KEY])
            ->getJson('/api/v1/dashboard/assigned-accounts?user_id=' . self::CREDIT_COUNSELOR_ID . '&period=' . $period);
    }

    /**
     * Account codes ("A12", "B04", …) listed under the classification groups of a Visit Plan response.
     * The code is the first word of the sample customer name. The "visited" list is not included.
     *
     * @return array<int, string>
     */
    protected function returnedAccountCodes(array $data): array
    {
        $codes = [];
        $walk = function (array $node) use (&$walk, &$codes) {
            foreach ($node as $key => $value) {
                if ($key === 'visited' || !is_array($value)) {
                    continue;
                }

                if (isset($value['customer_name'])) {
                    $codes[] = strtok($value['customer_name'], ' ');
                    continue;
                }

                $walk($value);
            }
        };
        $walk($data);

        sort($codes);

        return $codes;
    }
}
