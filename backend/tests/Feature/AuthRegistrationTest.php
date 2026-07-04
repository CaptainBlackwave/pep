<?php

namespace Tests\Feature;

use App\Core\Models\CommunityAccessCode;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles so assignRole('user') works during registration
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function createAccessCode(string $plainCode = 'private-code'): CommunityAccessCode
    {
        return CommunityAccessCode::create([
            'code_hash' => CommunityAccessCode::hashCode($plainCode),
            'label' => 'Test invite',
        ]);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $accessCode = $this->createAccessCode();

        $response = $this->postJson('/api/v1/register', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'username', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
        ]);

        $user = User::where('username', 'testplayer')->first();
        $accessCode->refresh();

        $this->assertNotNull($accessCode->used_at);
        $this->assertSame($user->id, $accessCode->used_by_user_id);
    }

    public function test_registration_requires_username(): void
    {
        $this->createAccessCode();

        $response = $this->postJson('/api/v1/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_requires_unique_email(): void
    {
        $this->createAccessCode();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'username' => 'newplayer',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_unique_username(): void
    {
        $this->createAccessCode();
        User::factory()->create(['username' => 'takenname']);

        $response = $this->postJson('/api/v1/register', [
            'username' => 'takenname',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $this->createAccessCode();

        $response = $this->postJson('/api/v1/register', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $this->createAccessCode();

        $response = $this->postJson('/api/v1/register', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_new_user_gets_default_game_stats(): void
    {
        $this->createAccessCode();

        $response = $this->postJson('/api/v1/register', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(201);

        $user = User::where('username', 'testplayer')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('player_profiles', [
            'user_id' => $user->id,
            'cash'    => 1000,
            'bullets' => 50,
            'energy'  => 100,
            'health'  => 100,
            'level'   => 1,
        ]);
    }

    public function test_registration_requires_valid_access_code(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'wrong-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['access_code']);
    }

    public function test_registration_access_code_can_only_be_used_once(): void
    {
        $this->createAccessCode();

        $this->postJson('/api/v1/register', [
            'username' => 'firstplayer',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ])->assertCreated();

        $response = $this->postJson('/api/v1/register', [
            'username' => 'secondplayer',
            'email' => 'second@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'access_code' => 'private-code',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['access_code']);
    }
}
