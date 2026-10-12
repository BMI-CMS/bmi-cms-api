<?php

namespace Tests\Feature\VisitPlan;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * GET /api/v1/dashboard/team-assigned-accounts: the Visit Plan accounts of a requester's team
 * (level 2 -> assigned_ch_id, 3 -> assigned_am_ar_id, 4 -> assigned_rm__dh_id), for one psgc_code,
 * grouped per credit counselor and classified like assigned-accounts.
 *
 * Sample data: every account has CH = cms user 6, AM/AR = 7, RM/DH = 8; credit counselors are
 * cms users 1 (CHINKEE TAN) and 2 (HENRY SY). Time is frozen at 2026-10-14 14:00 (Asia/Manila).
 */
class TeamAssignedAccountsTest extends VisitPlanTestCase
{
    private const NOW = '2026-10-14 14:00:00';

    private const CLUSTER_HEAD_ID = 6;

    private const ACCOUNT_MANAGER_ID = 7;

    private const DIVISION_HEAD_ID = 8;

    private const QUEZON_CITY = '137404000';

    private const MAKATI_CITY = '137602000';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();
    }

    public function test_request_is_validated(): void
    {
        $this->getTeam(self::CLUSTER_HEAD_ID, ['user_id' => self::CLUSTER_HEAD_ID, 'period' => 'monthly'])
            ->assertStatus(422)->assertJsonValidationErrors('psgc_code');
        $this->getTeam(self::CLUSTER_HEAD_ID, ['user_id' => self::CLUSTER_HEAD_ID, 'psgc_code' => self::QUEZON_CITY])
            ->assertStatus(422)->assertJsonValidationErrors('period');
        $this->getTeam(self::CLUSTER_HEAD_ID, ['psgc_code' => self::QUEZON_CITY, 'period' => 'monthly'])
            ->assertStatus(422)->assertJsonValidationErrors('user_id');
    }

    public function test_only_levels_2_to_4_may_request(): void
    {
        $this->getTeamFor(1, self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)->assertForbidden();
        $this->getTeamFor(self::CLUSTER_HEAD_ID, self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)->assertOk();
    }

    public function test_response_keeps_its_structure(): void
    {
        $data = $this->getTeamFor(self::CLUSTER_HEAD_ID, self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)
            ->assertOk()->json('data');

        $this->assertSame(['repossession_request', 'visit_plan', 'fec_approval', 'collections', 'pagination'], array_keys($data));
        $this->assertSame([5, 3, 5], [$data['repossession_request'], $data['fec_approval'], $data['collections']]);
        $this->assertNotEmpty($data['visit_plan']);

        foreach ($data['visit_plan'] as $counselor) {
            $this->assertSame(['credit_counselor_name', 'total_accounts', 'accounts'], array_keys($counselor));
        }
    }

    public function test_a_team_without_accounts_in_the_psgc_code_gets_an_empty_visit_plan(): void
    {
        $data = $this->getTeamFor(self::CLUSTER_HEAD_ID, self::CLUSTER_HEAD_ID, 'monthly', '999999999')
            ->assertOk()->json('data');

        $this->assertSame([], $data['visit_plan']);
    }

    public function test_accounts_are_grouped_per_credit_counselor_and_filtered_by_psgc_code(): void
    {
        $quezon = $this->team(self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY);
        $makati = $this->team(self::CLUSTER_HEAD_ID, 'monthly', self::MAKATI_CITY);

        $this->assertSame(['CHINKEE TAN'], array_keys($quezon));
        $this->assertSame(['CHINKEE TAN', 'HENRY SY'], $this->sorted(array_keys($makati)));

        foreach ([[$quezon, self::QUEZON_CITY], [$makati, self::MAKATI_CITY]] as [$counselors, $psgc]) {
            foreach ($counselors as $tree) {
                foreach ($this->accountsOf($tree) as $account) {
                    $this->assertSame($psgc, $account['psgc_code'], "Account {$account['account_number']} is outside {$psgc}.");
                }
            }
        }
    }

    public function test_it_returns_the_same_accounts_and_groups_as_assigned_accounts_once_each(): void
    {
        foreach (['monthly', 'daily'] as $period) {
            $expected = $this->assignedAccountsGroups(1, $period, self::QUEZON_CITY);
            $team = $this->team(self::CLUSTER_HEAD_ID, $period, self::QUEZON_CITY);

            $this->assertNotEmpty($expected);
            $this->assertSame($expected, $this->groupsOf($team['CHINKEE TAN']), "Wrong accounts or groups for {$period}.");
        }
    }

    public function test_another_counselors_accounts_appear_under_their_own_name(): void
    {
        foreach (['monthly', 'daily'] as $period) {
            $expected = $this->assignedAccountsGroups(2, $period, self::MAKATI_CITY);
            $team = $this->team(self::CLUSTER_HEAD_ID, $period, self::MAKATI_CITY);

            $this->assertSame(['7300000001' => 'regular|dpd_1_30_days'], $expected);
            $this->assertSame($expected, $this->groupsOf($team['HENRY SY']), "Wrong accounts or groups for {$period}.");
        }
    }

    public function test_daily_is_a_subset_of_monthly(): void
    {
        $daily = array_keys($this->groupsOf($this->team(self::CLUSTER_HEAD_ID, 'daily', self::QUEZON_CITY)['CHINKEE TAN']));
        $monthly = array_keys($this->groupsOf($this->team(self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)['CHINKEE TAN']));

        $this->assertNotEmpty($daily);
        $this->assertSame([], array_diff($daily, $monthly));
        $this->assertNotEmpty(array_diff($monthly, $daily), 'Monthly should also list the accounts due later in the month.');
    }

    public function test_total_accounts_is_the_number_of_accounts_listed_in_the_groups(): void
    {
        $visitPlan = $this->getTeamFor(self::CLUSTER_HEAD_ID, self::CLUSTER_HEAD_ID, 'monthly', self::MAKATI_CITY)
            ->assertOk()->json('data.visit_plan');

        $this->assertNotEmpty($visitPlan);

        foreach ($visitPlan as $counselor) {
            unset($counselor['accounts']['visited']);

            $this->assertSame(
                count($this->groupsOf($counselor['accounts'])),
                $counselor['total_accounts'],
                "total_accounts is wrong for {$counselor['credit_counselor_name']}.",
            );
        }
    }

    public function test_visited_accounts_are_listed_per_counselor_like_assigned_accounts(): void
    {
        foreach (['monthly', 'daily'] as $period) {
            $expected = $this->assignedAccountsVisited(1, $period, self::QUEZON_CITY);
            $visited = $this->visitedOf($this->getTeamPlan(self::CLUSTER_HEAD_ID, $period, self::QUEZON_CITY)['CHINKEE TAN']);

            if ($period === 'monthly') {
                $this->assertNotEmpty($expected, 'The sample data has no visited account this month.');
            }
            $this->assertSame($expected, $visited, "Wrong visited accounts for {$period}.");
        }
    }

    public function test_visited_is_always_present_and_is_not_mixed_with_the_groups(): void
    {
        $plan = $this->getTeamPlan(self::CLUSTER_HEAD_ID, 'monthly', self::MAKATI_CITY);

        foreach ($plan as $accounts) {
            $this->assertArrayHasKey('visited', $accounts);
            $this->assertIsArray($accounts['visited']);
        }
    }

    public function test_an_account_that_was_never_contacted_does_not_break_the_endpoint(): void
    {
        $groups = $this->groupsOf($this->team(self::CLUSTER_HEAD_ID, 'daily', self::QUEZON_CITY)['CHINKEE TAN']);
        $account = collect($this->accountsOf($this->team(self::CLUSTER_HEAD_ID, 'daily', self::QUEZON_CITY)['CHINKEE TAN']))
            ->first(fn (array $account) => !$account['is_contacted']);

        $this->assertNotNull($account, 'The sample data should contain a never-contacted account.');
        $this->assertArrayHasKey($account['account_number'], $groups);
    }

    public function test_level_3_and_level_4_requesters_see_their_own_team(): void
    {
        $clusterHead = $this->groupsOf($this->team(self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)['CHINKEE TAN']);

        foreach ([self::ACCOUNT_MANAGER_ID, self::DIVISION_HEAD_ID] as $requesterId) {
            $team = $this->team($requesterId, 'monthly', self::QUEZON_CITY);

            $this->assertSame($clusterHead, $this->groupsOf($team['CHINKEE TAN']), "Requester {$requesterId} sees a different team.");
        }
    }

    public function test_pagination_defaults_to_the_first_page_of_15_counselors(): void
    {
        $pagination = $this->getTeamFor(self::CLUSTER_HEAD_ID, self::CLUSTER_HEAD_ID, 'monthly', self::MAKATI_CITY)
            ->assertOk()->json('data.pagination');

        $this->assertSame(['current_page' => 1, 'per_page' => 15, 'total' => 2, 'last_page' => 1], $pagination);
    }

    public function test_pages_split_the_counselors_and_together_return_everything_once(): void
    {
        $full = $this->team(self::CLUSTER_HEAD_ID, 'monthly', self::MAKATI_CITY);
        $names = [];
        $groups = [];

        foreach ([1, 2] as $page) {
            $data = $this->getTeam(self::CLUSTER_HEAD_ID, [
                'user_id' => self::CLUSTER_HEAD_ID,
                'psgc_code' => self::MAKATI_CITY,
                'period' => 'monthly',
                'page' => $page,
                'per_page' => 1,
            ])->assertOk()->json('data');

            $this->assertSame(['current_page' => $page, 'per_page' => 1, 'total' => 2, 'last_page' => 2], $data['pagination']);
            $this->assertCount(1, $data['visit_plan']);

            $entry = $data['visit_plan'][0];
            $names[] = $entry['credit_counselor_name'];
            unset($entry['accounts']['visited']);
            $groups[$entry['credit_counselor_name']] = $this->groupsOf($entry['accounts']);
            $this->assertSame(count($groups[$entry['credit_counselor_name']]), $entry['total_accounts']);
        }

        $this->assertSame(array_keys($full), $names);
        $this->assertSame(array_map(fn (array $tree) => $this->groupsOf($tree), $full), $groups);
    }

    public function test_a_page_beyond_the_last_is_empty_but_keeps_the_pagination(): void
    {
        $data = $this->getTeam(self::CLUSTER_HEAD_ID, [
            'user_id' => self::CLUSTER_HEAD_ID,
            'psgc_code' => self::MAKATI_CITY,
            'period' => 'monthly',
            'page' => 5,
        ])->assertOk()->json('data');

        $this->assertSame([], $data['visit_plan']);
        $this->assertSame(['current_page' => 5, 'per_page' => 15, 'total' => 2, 'last_page' => 1], $data['pagination']);
    }

    public function test_pagination_parameters_are_validated(): void
    {
        $base = ['user_id' => self::CLUSTER_HEAD_ID, 'psgc_code' => self::MAKATI_CITY, 'period' => 'monthly'];

        $this->getTeam(self::CLUSTER_HEAD_ID, [...$base, 'page' => 0])->assertStatus(422)->assertJsonValidationErrors('page');
        $this->getTeam(self::CLUSTER_HEAD_ID, [...$base, 'per_page' => 0])->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getTeam(self::CLUSTER_HEAD_ID, [...$base, 'per_page' => 51])->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getTeam(self::CLUSTER_HEAD_ID, [...$base, 'page' => 'x'])->assertStatus(422)->assertJsonValidationErrors('page');
    }

    public function test_the_team_psgc_indexes_exist(): void
    {
        foreach (['assigned_ch_id', 'assigned_am_ar_id', 'assigned_rm__dh_id'] as $column) {
            $this->assertTrue(
                Schema::hasIndex('accounts', [$column, 'psgc_code']),
                "accounts has no index on ({$column}, psgc_code).",
            );
        }
    }

    public function test_a_requester_does_not_see_accounts_of_another_team(): void
    {
        DB::table('accounts')->where('account_number', '7100000006')->update(['assigned_ch_id' => 9]);

        $all = $this->groupsOf($this->team(self::CLUSTER_HEAD_ID, 'monthly', self::QUEZON_CITY)['CHINKEE TAN']);
        $this->assertArrayNotHasKey('7100000006', $all);
    }

    private function getTeam(int $requesterCmsUserId, array $query): TestResponse
    {
        Sanctum::actingAs(User::where('cms_user_id', $requesterCmsUserId)->firstOrFail());

        return $this->withHeaders(['BMI_CMS_KEY' => self::API_KEY])
            ->getJson('/api/v1/dashboard/team-assigned-accounts?' . http_build_query($query));
    }

    private function getTeamFor(int $requesterCmsUserId, int $userId, string $period, string $psgcCode): TestResponse
    {
        return $this->getTeam($requesterCmsUserId, ['user_id' => $userId, 'period' => $period, 'psgc_code' => $psgcCode]);
    }

    /**
     * credit_counselor_name => the accounts listed under it (flat), for a successful response.
     *
     * @return array<string, array<int, array>>
     */
    private function team(int $requesterCmsUserId, string $period, string $psgcCode): array
    {
        $team = $this->getTeamPlan($requesterCmsUserId, $period, $psgcCode);

        foreach ($team as $name => $accounts) {
            unset($team[$name]['visited']);
        }

        return $team;
    }

    /**
     * credit_counselor_name => the counselor's "accounts" (groups and "visited"), unchanged.
     *
     * @return array<string, array>
     */
    private function getTeamPlan(int $requesterCmsUserId, string $period, string $psgcCode): array
    {
        $visitPlan = $this->getTeamFor($requesterCmsUserId, $requesterCmsUserId, $period, $psgcCode)
            ->assertOk()->json('data.visit_plan');

        $team = [];
        foreach ($visitPlan as $counselor) {
            $team[$counselor['credit_counselor_name']] = $counselor['accounts'];
        }

        return $team;
    }

    /**
     * Sorted contact_recording_ids of the visited list.
     *
     * @return array<int, int>
     */
    private function visitedOf(array $accounts): array
    {
        $ids = array_column($accounts['visited'], 'contact_recording_id');
        sort($ids);

        return $ids;
    }

    /**
     * The reference: the sorted contact_recording_ids of the "visited" list of assigned-accounts
     * for a credit counselor, limited to one PSGC code.
     *
     * @return array<int, int>
     */
    private function assignedAccountsVisited(int $creditCounselorId, string $period, string $psgcCode): array
    {
        Sanctum::actingAs(User::where('cms_user_id', self::CREDIT_COUNSELOR_ID)->firstOrFail());

        $visited = $this->withHeaders(['BMI_CMS_KEY' => self::API_KEY])
            ->getJson("/api/v1/dashboard/assigned-accounts?user_id={$creditCounselorId}&period={$period}")
            ->assertOk()->json('data.visited');

        $ids = array_column(array_filter($visited, fn (array $row) => $row['psgc_code'] === $psgcCode), 'contact_recording_id');
        sort($ids);

        return $ids;
    }

    /**
     * account_number => "parent|group[|subgroup]" for every account in a classified accounts tree.
     *
     * @return array<string, string>
     */
    private function groupsOf(array $tree): array
    {
        $found = [];
        $walk = function (array $node, array $path) use (&$walk, &$found) {
            foreach ($node as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }

                if (isset($value['account_number'])) {
                    $found[$value['account_number']] = implode('|', $path);
                    continue;
                }

                $walk($value, is_int($key) ? $path : [...$path, $key]);
            }
        };
        $walk($tree, []);

        ksort($found);

        return $found;
    }

    private function accountsOf(array $tree): array
    {
        $accounts = [];
        $walk = function (array $node) use (&$walk, &$accounts) {
            foreach ($node as $value) {
                if (!is_array($value)) {
                    continue;
                }

                isset($value['account_number']) ? $accounts[] = $value : $walk($value);
            }
        };
        $walk($tree);

        return $accounts;
    }

    /**
     * The reference: what assigned-accounts lists for a credit counselor, limited to one PSGC code
     * ("visited" excluded), as account_number => group path.
     *
     * @return array<string, string>
     */
    private function assignedAccountsGroups(int $creditCounselorId, string $period, string $psgcCode): array
    {
        Sanctum::actingAs(User::where('cms_user_id', self::CREDIT_COUNSELOR_ID)->firstOrFail());

        $data = $this->withHeaders(['BMI_CMS_KEY' => self::API_KEY])
            ->getJson("/api/v1/dashboard/assigned-accounts?user_id={$creditCounselorId}&period={$period}")
            ->assertOk()->json('data');
        unset($data['visited']);

        $accounts = collect($this->accountsOf($data))->where('psgc_code', $psgcCode)->pluck('account_number')->all();

        return array_intersect_key($this->groupsOf($data), array_flip($accounts));
    }

    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
