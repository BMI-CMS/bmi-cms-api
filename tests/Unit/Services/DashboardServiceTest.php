<?php

namespace Tests\Unit\Services;

use App\Constants\Constants;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use App\Services\SettingService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DashboardServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const USER_ID = 7;

    public function test_accounts_are_grouped_by_psgc_code_instead_of_index(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000']),
            $this->visitRow(['id' => 2, 'psgc_code' => '0412345000']),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertSame([1380100000, '0412345000', 'visited'], array_keys($result));
    }

    public function test_numeric_psgc_codes_keep_their_value_when_visited_is_appended(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000']),
            $this->visitRow(['id' => 2, 'psgc_code' => '1380200000']),
            $this->visitRow(['id' => 3, 'psgc_code' => '1380300000']),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertArrayHasKey('1380100000', $result);
        $this->assertArrayHasKey('1380200000', $result);
        $this->assertArrayHasKey('1380300000', $result);
        $this->assertArrayNotHasKey(0, $result);
    }

    public function test_each_psgc_code_holds_only_its_own_accounts(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'account_number' => 'A-1', 'psgc_code' => '1380100000']),
            $this->visitRow(['id' => 2, 'account_number' => 'A-2', 'psgc_code' => '1380100000']),
            $this->visitRow(['id' => 3, 'account_number' => 'A-3', 'psgc_code' => '0412345000']),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertSame(['A-1', 'A-2'], $this->accountNumbers($result['1380100000']));
        $this->assertSame(['A-3'], $this->accountNumbers($result['0412345000']));
    }

    public function test_accounts_are_classified_within_their_psgc_code(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000', 'dpd_bucket' => 'DPD 91-120']),
            $this->visitRow(['id' => 2, 'psgc_code' => '0412345000', 'dpd_bucket' => 'DPD 1-30']),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertCount(1, $result['1380100000'][Constants::PRIORITIZATION][Constants::PROPERTY_FORREPOSSESSION]);
        $this->assertCount(1, $result['0412345000'][Constants::REGULAR][Constants::PROPERTY_DPD_1_30_DAYS]);
        $this->assertArrayNotHasKey(Constants::REGULAR, $result['1380100000']);
    }

    public function test_multiple_rows_of_one_account_merge_into_one_account_with_activity_records(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000', 'contact_recording_id' => 10, 'remarks' => 'first']),
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000', 'contact_recording_id' => 11, 'remarks' => 'second']),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $accounts = $this->flattenAccounts($result['1380100000']);

        $this->assertCount(1, $accounts);
        $this->assertSame(['first', 'second'], array_column($accounts[0]['activity_records'], 'remarks'));
    }

    public function test_account_without_contact_recording_has_no_activity_records(): void
    {
        $result = $this->makeService([
            $this->visitRow(['id' => 1, 'psgc_code' => '1380100000', 'contact_recording_id' => null]),
        ])->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertSame([], $this->flattenAccounts($result['1380100000'])[0]['activity_records']);
    }

    public function test_visited_accounts_are_returned_under_the_visited_key(): void
    {
        $visited = [['account_id' => 99]];

        $result = $this->makeService([], $visited)->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY);

        $this->assertSame(['visited' => $visited], $result);
    }

    public function test_daily_period_reads_daily_accounts(): void
    {
        $repository = Mockery::mock(DashboardRepository::class);
        $repository->shouldReceive('getDailyAccounts')->once()->with(self::USER_ID)
            ->andReturn(['for_visit' => [], 'visited' => []]);
        $repository->shouldNotReceive('getMonthlyAccounts');

        $service = new DashboardService($repository, Mockery::mock(SettingService::class));

        $this->assertSame(['visited' => []], $service->getAssignedAccounts(self::USER_ID, Constants::PERIOD_DAILY));
    }

    public function test_monthly_period_reads_monthly_accounts(): void
    {
        $repository = Mockery::mock(DashboardRepository::class);
        $repository->shouldReceive('getMonthlyAccounts')->once()->with(self::USER_ID)
            ->andReturn(['for_visit' => [], 'visited' => []]);
        $repository->shouldNotReceive('getDailyAccounts');

        $service = new DashboardService($repository, Mockery::mock(SettingService::class));

        $this->assertSame(['visited' => []], $service->getAssignedAccounts(self::USER_ID, Constants::PERIOD_MONTHLY));
    }

    /**
     * Builds the service with a repository stub that returns the given rows for the monthly period.
     */
    private function makeService(array $forVisit, array $visited = []): DashboardService
    {
        $repository = Mockery::mock(DashboardRepository::class);
        $repository->shouldReceive('getMonthlyAccounts')->with(self::USER_ID)
            ->andReturn(['for_visit' => $forVisit, 'visited' => $visited]);

        return new DashboardService($repository, Mockery::mock(SettingService::class));
    }

    /**
     * Builds one repository row with defaults that classify as a regular NP 0 account.
     */
    private function visitRow(array $overrides = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'account_number' => 'ACC-1',
            'customer_name' => 'Juan Dela Cruz',
            'monthly_amortization' => 1000,
            'past_due_balance' => 0,
            'dpd_bucket' => 'DPD 0',
            'outstanding_balance' => 50000,
            'last_payment_date' => null,
            'asset' => 'Motorcycle',
            'payment_status' => null,
            'pre_qualified_for_restructuring' => 0,
            'is_force_prioritized' => 0,
            'psgc_code' => '1380100000',
            'is_contacted' => 0,
            'assigned_cc_id' => self::USER_ID,
            'assigned_ch_id' => null,
            'assigned_am_ar_id' => null,
            'assigned_rm_dh_id' => null,
            'unit_lot_block' => null,
            'street_name' => null,
            'subdivision_village' => null,
            'province' => null,
            'postal_code' => null,
            'barangay' => null,
            'city_municipality' => null,
            'region' => null,
            'contact_number' => null,
            'email' => null,
            'social_media_account' => null,
            'due_date' => null,
            'current_category' => null,
            'next_action_date' => null,
            'next_action_plan' => null,
            'contact_recording_id' => null,
            'follow_up_mode' => null,
            'reason_for_default' => null,
            'action_taken' => null,
            'remarks' => null,
            'geotagging' => null,
            'recorded_by' => null,
            'contact_date' => null,
        ], $overrides);
    }

    /**
     * Flattens a classified PSGC tree into its list of account arrays.
     */
    private function flattenAccounts(array $classified): array
    {
        $accounts = [];

        $collect = function (array $node) use (&$collect, &$accounts) {
            if (array_key_exists('account_id', $node)) {
                $accounts[] = $node;

                return;
            }

            foreach ($node as $child) {
                $collect($child);
            }
        };
        $collect($classified);

        return $accounts;
    }

    /**
     * Returns the account numbers found anywhere in a classified PSGC tree.
     */
    private function accountNumbers(array $classified): array
    {
        return array_column($this->flattenAccounts($classified), 'account_number');
    }
}
