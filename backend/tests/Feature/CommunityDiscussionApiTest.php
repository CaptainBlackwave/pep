<?php

namespace Tests\Feature;

use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionCategory;
use App\Core\Models\User;
use App\Core\Middleware\VerifyLicense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityDiscussionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_discussion_index_returns_discussions_and_categories(): void
    {
        $category = CommunityDiscussionCategory::create([
            'name' => 'Vendor Talk',
            'slug' => 'vendor-talk',
            'description' => 'Vendor conversations',
            'icon' => 'box',
            'color' => 'purple',
        ]);

        CommunityDiscussion::create([
            'category_id' => $category->id,
            'author_name' => 'AlexM',
            'title' => 'Retatrutide experiences',
            'slug' => 'retatrutide-experiences',
            'tag' => 'Discussion',
            'excerpt' => 'Real progress tracking',
            'body' => 'Let us compare real progress notes.',
            'status' => 'published',
            'views_count' => 1200,
            'last_reply_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/community/discussions');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'retatrutide-experiences')
            ->assertJsonPath('data.0.category.slug', 'vendor-talk')
            ->assertJsonPath('meta.categories.0.slug', 'vendor-talk');
    }

    public function test_private_discussion_detail_includes_replies_and_increments_views(): void
    {
        $discussion = CommunityDiscussion::create([
            'author_name' => 'AlexM',
            'title' => 'Retatrutide experiences',
            'slug' => 'retatrutide-experiences',
            'tag' => 'Discussion',
            'excerpt' => 'Real progress tracking',
            'body' => 'Let us compare real progress notes.',
            'status' => 'published',
            'views_count' => 7,
            'last_reply_at' => now(),
        ]);

        $discussion->replies()->create([
            'author_name' => 'Jason93',
            'body' => 'This has been useful so far.',
            'votes_count' => 3,
        ]);
        $discussion->forceFill(['replies_count' => 1])->save();

        $response = $this->getJson('/api/v1/community/discussions/retatrutide-experiences');

        $response->assertOk()
            ->assertJsonPath('data.views', 8)
            ->assertJsonPath('data.reply_items.0.author.name', 'Jason93');
    }

    public function test_authenticated_user_can_create_discussion_and_reply(): void
    {
        $user = User::factory()->create([
            'name' => 'Forum User',
            'username' => 'ForumUser',
        ]);
        $category = CommunityDiscussionCategory::create([
            'name' => 'General Discussion',
            'slug' => 'general-discussion',
            'icon' => 'discussions',
            'color' => 'purple',
        ]);

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/v1/community/discussions', [
            'title' => 'New community question',
            'body' => 'Has anyone compared batch-specific reports recently?',
            'category_slug' => $category->slug,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.slug', 'new-community-question')
            ->assertJsonPath('data.author.name', 'ForumUser');

        $replyResponse = $this->postJson('/api/v1/community/discussions/new-community-question/replies', [
            'body' => 'Adding a follow-up with details.',
        ]);

        $replyResponse->assertCreated()
            ->assertJsonPath('data.author.name', 'ForumUser');

        $this->assertDatabaseHas('community_discussions', [
            'slug' => 'new-community-question',
            'replies_count' => 1,
        ]);
    }

    public function test_admin_can_moderate_community_discussion(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        $discussion = CommunityDiscussion::create([
            'author_name' => 'AlexM',
            'title' => 'Needs moderation',
            'slug' => 'needs-moderation',
            'tag' => 'Discussion',
            'excerpt' => 'Moderation target',
            'body' => 'This discussion should be moderated.',
            'status' => 'published',
            'views_count' => 10,
            'last_reply_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/community/discussions/{$discussion->slug}", [
            'status' => 'hidden',
            'is_locked' => true,
            'is_pinned' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'hidden')
            ->assertJsonPath('data.is_locked', true)
            ->assertJsonPath('data.is_pinned', true);

        $this->assertDatabaseHas('community_discussions', [
            'slug' => 'needs-moderation',
            'status' => 'hidden',
            'is_locked' => true,
            'is_pinned' => true,
        ]);
    }
}
