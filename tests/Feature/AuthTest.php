<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'nonexistent_user',
            'password' => 'wrongpassword',
            'phone_imei' => '123456789012345',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_login_fails_with_device_mismatch_when_phone_imei_does_not_match(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('secret123'),
            'phone_imei' => '111112222233333',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'secret123',
            'phone_imei' => '999998888877777',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Device not recognized. Please use your registered device.',
            ]);
    }

    public function test_login_validation_fails_when_phone_imei_is_missing_or_invalid(): void
    {
        // Missing phone_imei
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'secret123',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_imei']);

        // Invalid length (not 15 digits)
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'secret123',
            'phone_imei' => '12345',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_imei']);

        // Non-numeric
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'secret123',
            'phone_imei' => 'abcdefghijklmno',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_imei']);
    }

    public function test_login_succeeds_and_returns_bearer_token(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('secret123'),
            'phone_imei' => '123456789012345',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'secret123',
            'phone_imei' => '123456789012345',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'username',
                    'name',
                    'user_level_id',
                    'level',
                    'position',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertEquals($user->name, $response->json('user.name'));
        $this->assertEquals($user->user_level_id, $response->json('user.user_level_id'));
        $this->assertEquals($user->user_level_id, $response->json('user.level'));
        $this->assertEquals($user->userLevel->name, $response->json('user.position'));
    }

    public function test_me_route_rejects_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_me_route_rejects_unauthenticated_request_without_accept_header(): void
    {
        $response = $this->get('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_me_route_succeeds_with_valid_bearer_token(): void
    {
        $user = User::factory()->create([
            'username' => 'authuser',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ],
            ]);
    }

    public function test_logout_revokes_token_and_subsequent_request_is_rejected(): void
    {
        $user = User::factory()->create([
            'username' => 'logoutuser',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Verify access before logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');
        $response->assertStatus(200);

        // Perform logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');
        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        app('auth')->forgetGuards();

        // Verify token is revoked and /me returns 401
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');
        $subsequentResponse->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
