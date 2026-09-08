<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAttestation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAttestationTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthUser(): User
    {
        return User::factory()->create();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/user/attestation', [
            'is_attested' => true,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_attestation_accept(): void
    {
        $authUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/v1/user/attestation', [
            'is_attested' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Attestation Accepted.',
                'data' => true,
            ]);

        $this->assertDatabaseHas('user_attestations', [
            'user_id' => $authUser->id,
            'is_attested' => 1,
        ]);
    }

    public function test_authenticated_user_can_submit_attestation_decline(): void
    {
        $authUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/v1/user/attestation', [
            'is_attested' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Attestation Declined.',
                'data' => false,
            ]);

        $this->assertDatabaseHas('user_attestations', [
            'user_id' => $authUser->id,
            'is_attested' => 0,
        ]);
    }

    public function test_client_supplied_user_id_is_ignored_preventing_idor(): void
    {
        $authUser = $this->createAuthUser();
        $otherUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        // Client attempts to pass a different user's ID
        $response = $this->postJson('/api/v1/user/attestation', [
            'user_id' => $otherUser->id,
            'is_attested' => true,
        ]);

        $response->assertStatus(200);

        // Verified that attestation was stored for the authenticated user, NOT the spoofed user
        $this->assertDatabaseHas('user_attestations', [
            'user_id' => $authUser->id,
            'is_attested' => 1,
        ]);
        $this->assertDatabaseMissing('user_attestations', [
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_audit_trail_creates_multiple_records_on_subsequent_submissions(): void
    {
        $authUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        $this->postJson('/api/v1/user/attestation', ['is_attested' => true])->assertStatus(200);
        $this->postJson('/api/v1/user/attestation', ['is_attested' => false])->assertStatus(200);
        $this->postJson('/api/v1/user/attestation', ['is_attested' => true])->assertStatus(200);

        $this->assertDatabaseCount('user_attestations', 3);
        $this->assertEquals(3, UserAttestation::where('user_id', $authUser->id)->count());
    }

    public function test_validation_fails_for_missing_or_invalid_is_attested(): void
    {
        $authUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/v1/user/attestation', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_attested']);

        $response = $this->postJson('/api/v1/user/attestation', [
            'is_attested' => 'not-a-boolean',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_attested']);
    }

    public function test_deleting_user_cascades_and_removes_attestations(): void
    {
        $authUser = $this->createAuthUser();
        Sanctum::actingAs($authUser);

        $this->postJson('/api/v1/user/attestation', ['is_attested' => true])->assertStatus(200);
        $this->assertDatabaseCount('user_attestations', 1);

        // Delete user and ensure foreign key cascades
        $authUser->delete();
        $this->assertDatabaseCount('user_attestations', 0);
    }
}
