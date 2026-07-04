<?php

namespace Tests\Feature;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile_and_settings(): void
    {
        $user = User::factory()->create([
            'username' => 'tester',
            'name' => 'Tester',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/profile', [
            'username' => 'researcher',
            'name' => 'Researcher',
            'bio' => 'Community researcher',
            'timezone' => 'Europe/London',
            'locale' => 'en-GB',
            'website_url' => 'https://example.com',
        ])->assertOk()
            ->assertJsonPath('user.username', 'researcher')
            ->assertJsonPath('user.bio', 'Community researcher');

        $this->patchJson('/api/v1/user', [
            'email_notifications' => false,
            'push_notifications' => true,
            'show_online' => false,
            'compact_discussions' => true,
            'language' => 'en-GB',
        ])->assertOk()
            ->assertJsonPath('user.email_notifications', false)
            ->assertJsonPath('user.compact_discussions', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'researcher',
            'bio' => 'Community researcher',
        ]);
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'email_notifications' => false,
            'show_online' => false,
            'compact_discussions' => true,
        ]);
    }

    public function test_notification_preferences_and_privacy_round_trip(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/notification-settings', [
            'emailNotifications' => false,
            'pushNotifications' => false,
            'soundEnabled' => true,
        ])->assertOk()
            ->assertJsonPath('emailNotifications', false)
            ->assertJsonPath('pushNotifications', false)
            ->assertJsonPath('soundEnabled', true);

        $this->patchJson('/api/v1/user/preferences', [
            'compactMode' => true,
            'showOnlineMembers' => false,
            'rememberContentFilters' => true,
        ])->assertOk()
            ->assertJsonPath('compactMode', true)
            ->assertJsonPath('showOnlineMembers', false);

        $this->patchJson('/api/v1/user/privacy', [
            'profile_visibility' => 'members_only',
            'direct_messages' => 'nobody',
            'allow_analytics' => true,
        ])->assertOk()
            ->assertJsonPath('profile_visibility', 'members_only')
            ->assertJsonPath('direct_messages', 'nobody')
            ->assertJsonPath('allow_analytics', true);
    }

    public function test_user_can_manage_personal_api_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/v1/user/api-tokens', [
            'name' => 'Research API',
            'abilities' => ['research:read'],
        ])->assertCreated()
            ->assertJsonPath('token.name', 'Research API')
            ->assertJsonStructure(['plain_text_token']);

        $tokenId = $createResponse->json('token.id');

        $this->getJson('/api/v1/user/api-tokens')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Research API');

        $this->getJson('/api/v1/user/sessions')
            ->assertOk()
            ->assertJsonPath('tokens.0.name', 'Research API');

        $this->deleteJson("/api/v1/user/api-tokens/{$tokenId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
