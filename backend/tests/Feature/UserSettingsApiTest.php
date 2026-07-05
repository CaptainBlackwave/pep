<?php

namespace Tests\Feature;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'email' => 'researcher@example.com',
            'bio' => 'Community researcher',
            'timezone' => 'Europe/London',
            'locale' => 'en-GB',
            'website_url' => 'https://example.com',
        ])->assertOk()
            ->assertJsonPath('user.username', 'researcher')
            ->assertJsonPath('user.email', 'researcher@example.com')
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
            'email' => 'researcher@example.com',
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

    public function test_authenticated_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'tester',
        ]);
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/user/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $avatarUrl = $response->json('avatar');
        $this->assertStringStartsWith('http://localhost/storage/avatars/', $avatarUrl);
        $this->assertSame($avatarUrl, $response->json('user.profile_photo_path'));
        $this->assertSame($avatarUrl, $user->fresh()->profile_photo_path);
        Storage::disk('public')->assertExists(Str::after($avatarUrl, '/storage/'));
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

    public function test_user_can_revoke_session_token_from_settings(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $token = $user->createToken('Mobile browser');
        $tokenId = $token->accessToken->id;

        $this->deleteJson("/api/v1/user/sessions/{$tokenId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_user_can_block_and_unblock_members(): void
    {
        $user = User::factory()->create(['username' => 'viewer']);
        $blocked = User::factory()->create(['username' => 'blocked-member']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user/blocked-users', [
            'user_id' => $blocked->id,
        ])->assertOk()
            ->assertJsonPath('data.0.username', 'blocked-member');

        $this->assertDatabaseHas('community_user_blocks', [
            'user_id' => $user->id,
            'blocked_user_id' => $blocked->id,
        ]);

        $this->deleteJson("/api/v1/user/blocked-users/{$blocked->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('community_user_blocks', [
            'user_id' => $user->id,
            'blocked_user_id' => $blocked->id,
        ]);
    }

    public function test_user_can_block_member_by_username(): void
    {
        $user = User::factory()->create(['username' => 'viewer']);
        $blocked = User::factory()->create(['username' => 'blocked-by-name']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user/blocked-users', [
            'username' => 'blocked-by-name',
        ])->assertOk()
            ->assertJsonPath('data.0.username', 'blocked-by-name');

        $this->assertDatabaseHas('community_user_blocks', [
            'user_id' => $user->id,
            'blocked_user_id' => $blocked->id,
        ]);
    }

    public function test_user_can_sync_and_toggle_community_actions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/user-actions/toggle', [
            'action' => 'follow',
            'target_type' => 'discussion',
            'target_key' => 'introduce-yourself',
        ])->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('data.followed_discussions.0', 'introduce-yourself');

        $this->postJson('/api/v1/community/user-actions/toggle', [
            'action' => 'bookmark',
            'target_type' => 'content',
            'target_key' => 'storage-guide',
        ])->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('data.bookmarked_content.0', 'storage-guide');

        $this->postJson('/api/v1/community/user-actions/toggle', [
            'action' => 'bookmark',
            'target_type' => 'product',
            'target_key' => 'vendor:tirzepatide',
        ])->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('data.bookmarked_products.0', 'vendor:tirzepatide');

        $this->getJson('/api/v1/community/user-actions')
            ->assertOk()
            ->assertJsonPath('data.followed_discussions.0', 'introduce-yourself')
            ->assertJsonPath('data.bookmarked_content.0', 'storage-guide')
            ->assertJsonPath('data.bookmarked_products.0', 'vendor:tirzepatide');

        $this->postJson('/api/v1/community/user-actions/toggle', [
            'action' => 'follow',
            'target_type' => 'discussion',
            'target_key' => 'introduce-yourself',
        ])->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonMissingPath('data.followed_discussions.0');

        $this->assertDatabaseMissing('community_user_actions', [
            'user_id' => $user->id,
            'action' => 'follow',
            'target_type' => 'discussion',
            'target_key' => 'introduce-yourself',
        ]);
    }
}
