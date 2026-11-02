<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CmsUser;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tests the login freeze, lock and unlock rules.
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    private const USERNAME = 'lockoutuser';
    private const PASSWORD = 'Correct@123';
    private const IMEI = '123456789012345';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();

        $company = Company::query()->forceCreate(['name' => 'Test Company']);
        $role = Role::query()->forceCreate(['company_id' => $company->id, 'name' => 'Credit Counselor']);
        $profile = CmsUser::query()->forceCreate([
            'name' => 'LOCKOUT USER',
            'role_id' => $role->id,
            'company_id' => $company->id,
            'level' => 1,
            'psgc_code' => '137404000',
        ]);

        $this->user = User::query()->forceCreate([
            'username' => self::USERNAME,
            'password' => Hash::make(self::PASSWORD),
            'cms_user_id' => $profile->id,
            'phone_imei' => self::IMEI,
        ]);
    }

    private function login(string $password, string $imei = self::IMEI, string $username = self::USERNAME): TestResponse
    {
        app('auth')->forgetGuards();

        return $this->withHeaders(['x-api-key' => config('services.api.key')])
            ->postJson('/api/v1/login', [
                'username' => $username,
                'password' => $password,
                'phone_imei' => $imei,
            ]);
    }

    private function failTimes(int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->login('wrong-password');
        }
    }

    private function freshUser(): User
    {
        return $this->user->fresh();
    }

    public function test_first_two_wrong_passwords_return_invalid_credentials(): void
    {
        $this->login('wrong-password')->assertStatus(401)->assertExactJson(['message' => 'Invalid credentials.']);
        $this->login('wrong-password')->assertStatus(401)->assertExactJson(['message' => 'Invalid credentials.']);

        $user = $this->freshUser();
        $this->assertSame(2, $user->failed_login_attempts);
        $this->assertNull($user->login_frozen_until);
        $this->assertNull($user->locked_at);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_third_wrong_password_freezes_login_for_15_minutes(): void
    {
        $this->failTimes(2);

        $response = $this->login('wrong-password');

        $response->assertStatus(429)
            ->assertHeader('Retry-After', '900')
            ->assertExactJson([
                'message' => 'Too many failed login attempts. Please try again in 15 minutes.',
                'retry_after' => 900,
            ]);

        $user = $this->freshUser();
        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertTrue($user->login_frozen_until->equalTo(now()->addMinutes(15)));
        $this->assertNull($user->locked_at);

        $log = AuditLog::sole();
        $this->assertSame('login_frozen', $log->action);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('users', $log->table_name);
        $this->assertSame($user->id, $log->record_id);
        $this->assertSame(self::IMEI, $log->device_id);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame(2, $log->old_values['failed_login_attempts']);
        $this->assertNull($log->old_values['login_frozen_until']);
        $this->assertSame(3, $log->new_values['failed_login_attempts']);
        $this->assertSame(now()->addMinutes(15)->toDateTimeString(), $log->new_values['login_frozen_until']);
    }

    public function test_attempts_while_frozen_are_rejected_and_not_counted(): void
    {
        $this->failTimes(3);
        $this->travel(5)->minutes();

        $this->login(self::PASSWORD)
            ->assertStatus(429)
            ->assertHeader('Retry-After', '600')
            ->assertJson([
                'message' => 'Too many failed login attempts. Please try again in 10 minutes.',
                'retry_after' => 600,
            ]);
        $this->login('wrong-password')->assertStatus(429);

        $this->assertSame(3, $this->freshUser()->failed_login_attempts);
        $this->assertSame(1, AuditLog::count());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_freeze_message_rounds_remaining_time_up_to_whole_minutes(): void
    {
        $this->failTimes(3);
        $this->travel(14 * 60 + 30)->seconds();

        $this->login('wrong-password')
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Too many failed login attempts. Please try again in 1 minute.',
                'retry_after' => 30,
            ]);
    }

    public function test_two_wrong_passwords_after_the_freeze_lock_the_account(): void
    {
        $this->failTimes(3);
        $this->travel(15)->minutes();

        $this->login('wrong-password')->assertStatus(401)->assertExactJson(['message' => 'Invalid credentials.']);
        $this->assertSame(4, $this->freshUser()->failed_login_attempts);

        $this->login('wrong-password')
            ->assertStatus(423)
            ->assertExactJson([
                'message' => 'Your account is locked due to too many failed login attempts. Please contact your administrator.',
            ]);

        $user = $this->freshUser();
        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertTrue($user->locked_at->equalTo(now()));
        $this->assertNull($user->login_frozen_until);

        $log = AuditLog::where('action', 'login_locked')->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($user->id, $log->record_id);
        $this->assertSame(4, $log->old_values['failed_login_attempts']);
        $this->assertSame(5, $log->new_values['failed_login_attempts']);
        $this->assertSame(now()->toDateTimeString(), $log->new_values['locked_at']);
        $this->assertSame(2, AuditLog::count());
    }

    public function test_locked_account_rejects_the_correct_password_until_unlocked(): void
    {
        $this->failTimes(3);
        $this->travel(15)->minutes();
        $this->failTimes(2);

        $this->login(self::PASSWORD)->assertStatus(423);

        $this->travel(1)->days();
        $this->login(self::PASSWORD)->assertStatus(423);
        $this->login('wrong-password')->assertStatus(423);

        $this->assertSame(5, $this->freshUser()->failed_login_attempts);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_successful_login_after_the_freeze_resets_the_counter(): void
    {
        $this->failTimes(3);
        $this->travel(15)->minutes();

        $this->login(self::PASSWORD)->assertOk();

        $user = $this->freshUser();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->login_frozen_until);

        $this->login('wrong-password')->assertStatus(401);
        $this->login('wrong-password')->assertStatus(401);
        $this->login('wrong-password')->assertStatus(429);
    }

    public function test_successful_login_before_the_freeze_resets_the_counter(): void
    {
        $this->failTimes(2);
        $this->login(self::PASSWORD)->assertOk();
        $this->failTimes(2);

        $this->assertSame(2, $this->freshUser()->failed_login_attempts);
        $this->login(self::PASSWORD)->assertOk();
        $this->assertSame(0, $this->freshUser()->failed_login_attempts);
    }

    public function test_failed_attempts_are_not_forgotten_over_time(): void
    {
        $this->failTimes(2);
        $this->travel(7)->days();

        $this->login('wrong-password')->assertStatus(429);
    }

    public function test_correct_password_from_unregistered_device_is_not_counted(): void
    {
        $this->failTimes(2);

        $this->login(self::PASSWORD, '999998888877777')->assertStatus(403);
        $this->login(self::PASSWORD, '999998888877777')->assertStatus(403);

        $this->assertSame(2, $this->freshUser()->failed_login_attempts);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_unknown_username_is_not_tracked(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->login('wrong-password', self::IMEI, 'nobody')->assertStatus(401);
        }

        $this->assertSame(0, $this->freshUser()->failed_login_attempts);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_unlock_command_unlocks_a_locked_account(): void
    {
        $this->failTimes(3);
        $this->travel(15)->minutes();
        $this->failTimes(2);

        $this->artisan('user:unlock', ['username' => self::USERNAME])
            ->expectsOutputToContain('was unlocked')
            ->assertExitCode(0);

        $user = $this->freshUser();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_at);
        $this->assertNull($user->login_frozen_until);

        $log = AuditLog::where('action', 'login_unlocked')->sole();
        $this->assertNull($log->user_id);
        $this->assertNull($log->device_id);
        $this->assertSame($user->id, $log->record_id);
        $this->assertSame(5, $log->old_values['failed_login_attempts']);
        $this->assertNotNull($log->old_values['locked_at']);
        $this->assertSame(0, $log->new_values['failed_login_attempts']);
        $this->assertNull($log->new_values['locked_at']);

        $this->login(self::PASSWORD)->assertOk();
    }

    public function test_unlock_command_clears_a_freeze(): void
    {
        $this->failTimes(3);

        $this->artisan('user:unlock', ['username' => self::USERNAME])->assertExitCode(0);

        $this->assertNull($this->freshUser()->login_frozen_until);
        $this->assertSame(1, AuditLog::where('action', 'login_unlocked')->count());
        $this->login(self::PASSWORD)->assertOk();
    }

    public function test_unlock_command_changes_nothing_for_an_account_that_is_not_locked(): void
    {
        $this->failTimes(1);

        $this->artisan('user:unlock', ['username' => self::USERNAME])
            ->expectsOutputToContain('is not locked or frozen')
            ->assertExitCode(0);

        $this->assertSame(1, $this->freshUser()->failed_login_attempts);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_unlock_command_fails_for_unknown_username(): void
    {
        $this->artisan('user:unlock', ['username' => 'nobody'])
            ->expectsOutputToContain('was not found')
            ->assertExitCode(1);
    }
}
