<?php

namespace Tests\Feature\VisitPlan;

use App\Models\AccountClassification;
use App\Models\User;
use Database\Seeders\AccountClassificationSeeder;
use Database\Seeders\CollectionAddressSeeder;
use Database\Seeders\CustomerDetailSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * GET /api/v1/dashboard/assigned-accounts-psgc: the monthly Visit Plan accounts of the credit
 * counselor (the same accounts as assigned-accounts?period=monthly) grouped by "psgc_code - city".
 *
 * Time is frozen at 2026-10-14 14:00 (application timezone, Asia/Manila).
 */
class AssignedAccountsPsgcTest extends VisitPlanTestCase
{
    private const NOW = '2026-10-14 14:00:00';

    private const GROUPS = [
        '137403000 - Pasig City',
        '137404000 - Quezon City',
        '137602000 - Makati City',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();
        $this->seed([CustomerDetailSeeder::class, CollectionAddressSeeder::class]);
    }

    public function test_period_is_not_needed_and_user_id_is_required(): void
    {
        $this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->assertOk();
        $this->getPsgc('')->assertStatus(422)->assertJsonValidationErrors('user_id');
    }

    public function test_accounts_are_grouped_by_psgc_code_and_city(): void
    {
        $data = $this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->assertOk()->json('data');

        $this->assertSame(self::GROUPS, $this->sorted(array_keys($data)));

        foreach ($data as $groupKey => $accounts) {
            $this->assertNotEmpty($accounts, "Group {$groupKey} is empty.");

            foreach ($accounts as $account) {
                $this->assertSame($account['psgc_code'] . ' - ' . $account['city_municipality'], $groupKey, "Account {$account['account_number']} is in the wrong group.");
            }
        }
    }

    public function test_returns_the_same_accounts_as_the_monthly_visit_plan_once_each(): void
    {
        $psgc = $this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->json('data');
        $monthly = $this->getVisitPlan('monthly')->assertOk()->json('data');

        $returned = collect($psgc)->flatten(1)->pluck('customer_name')->map(fn ($name) => strtok($name, ' '))->sort()->values()->all();

        $this->assertSame($this->returnedAccountCodes($monthly), $returned);
        $this->assertSame($returned, array_values(array_unique($returned)), 'An account is listed more than once.');
        $this->assertNotContains('C01', $returned, 'Another credit counselor\'s account was returned.');
    }

    public function test_classification_is_the_one_assigned_accounts_gives(): void
    {
        $expected = $this->classificationsFromAssignedAccounts($this->getVisitPlan('monthly')->json('data'));
        $accounts = collect($this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->json('data'))->flatten(1);

        $this->assertNotEmpty($expected);

        foreach ($accounts as $account) {
            $this->assertSame(
                $expected[$account['account_number']],
                $account['account_classification'],
                "Account {$account['account_number']} is classified differently from assigned-accounts.",
            );
        }
    }

    public function test_an_unclassifiable_account_is_kept_with_a_null_classification(): void
    {
        DB::table('accounts')->where('account_number', '7100000006')->update(['dpd_bucket' => 'DPD 15']);

        $accounts = collect($this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->assertOk()->json('data'))->flatten(1);
        $unclassified = $accounts->firstWhere('account_number', '7100000006');

        $this->assertNotNull($unclassified, 'The unclassifiable account was dropped.');
        $this->assertNull($unclassified['account_classification']);
        $this->assertSame(
            1,
            $accounts->whereNull('account_classification')->count(),
            'Only the account with the unknown DPD bucket should be unclassified.',
        );
    }

    public function test_account_entry_has_its_address_contact_and_assignment_details(): void
    {
        $accounts = collect($this->getPsgc('user_id=' . self::CREDIT_COUNSELOR_ID)->json('data'))->flatten(1);
        $account = $accounts->firstWhere('account_number', '7100000001');
        $stored = DB::table('accounts')->where('account_number', '7100000001')->first();

        $this->assertSame((int) $stored->assigned_am_ar_id, $account['assigned_am_id']);
        $this->assertSame((int) $stored->assigned_rm__dh_id, $account['assigned_dh_id']);
        $this->assertNotNull($account['barangay']);
        $this->assertNotNull($account['contact_number']);
        $this->assertIsArray($account['activity_records']);
    }

    private function getPsgc(string $query): TestResponse
    {
        Sanctum::actingAs(User::where('cms_user_id', self::CREDIT_COUNSELOR_ID)->firstOrFail());

        return $this->withHeaders(['BMI_CMS_KEY' => self::API_KEY])
            ->getJson('/api/v1/dashboard/assigned-accounts-psgc' . ($query === '' ? '' : '?' . $query));
    }

    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    /**
     * account_number => classification label, from where assigned-accounts lists each account
     * (the response path of the classification), excluding "visited".
     *
     * @return array<string, string>
     */
    private function classificationsFromAssignedAccounts(array $data): array
    {
        $labels = AccountClassification::query()->get()
            ->mapWithKeys(fn ($classification) => [implode('|', $classification->response_path) => $classification->label])
            ->all();

        $found = [];
        $walk = function (array $node, array $path) use (&$walk, &$found, $labels) {
            foreach ($node as $key => $value) {
                if ($path === [] && $key === 'visited' || !is_array($value)) {
                    continue;
                }

                if (isset($value['account_number'])) {
                    $found[$value['account_number']] = $labels[implode('|', $path)] ?? null;
                    continue;
                }

                $walk($value, is_int($key) ? $path : [...$path, $key]);
            }
        };
        $walk($data, []);

        return $found;
    }
}
