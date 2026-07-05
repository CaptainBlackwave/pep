<?php

namespace Tests\Feature;

use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionCategory;
use App\Core\Models\CommunityDiscussionReport;
use App\Core\Models\CommunityDiscussionVote;
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

    public function test_authenticated_user_can_reply_with_gif_attachment_metadata(): void
    {
        $discussion = CommunityDiscussion::create([
            'author_name' => 'AlexM',
            'title' => 'Attachment topic',
            'slug' => 'attachment-topic',
            'body' => 'Testing attachments.',
            'status' => 'published',
            'last_reply_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/community/discussions/{$discussion->slug}/replies", [
            'body' => 'Here is the chart GIF.',
            'attachment_name' => 'chart.gif',
            'attachment_url' => 'https://example.com/chart.gif',
            'attachment_type' => 'gif',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attachment_name', 'chart.gif')
            ->assertJsonPath('data.attachment_url', 'https://example.com/chart.gif')
            ->assertJsonPath('data.attachment_meta.type', 'gif');

        $this->assertDatabaseHas('community_discussion_replies', [
            'discussion_id' => $discussion->id,
            'attachment_name' => 'chart.gif',
            'attachment_url' => 'https://example.com/chart.gif',
        ]);
    }

    public function test_authenticated_user_can_vote_on_discussions_and_replies(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $discussion = CommunityDiscussion::create([
            'author_name' => 'AlexM',
            'title' => 'Vote topic',
            'slug' => 'vote-topic',
            'body' => 'Testing votes.',
            'status' => 'published',
            'last_reply_at' => now(),
        ]);
        $reply = $discussion->replies()->create([
            'author_name' => 'Jason93',
            'body' => 'Useful context.',
        ]);

        $discussionVote = $this->postJson("/api/v1/community/discussions/{$discussion->slug}/vote", [
            'value' => 1,
        ]);
        $replyVote = $this->postJson("/api/v1/community/discussion-replies/{$reply->id}/vote", [
            'value' => -1,
        ]);
        $replyToggle = $this->postJson("/api/v1/community/discussion-replies/{$reply->id}/vote", [
            'value' => -1,
        ]);

        $discussionVote->assertOk()
            ->assertJsonPath('data.vote_score', 1)
            ->assertJsonPath('data.viewer_vote', 1);
        $replyVote->assertOk()
            ->assertJsonPath('data.votes', -1)
            ->assertJsonPath('data.viewer_vote', -1);
        $replyToggle->assertOk()
            ->assertJsonPath('data.votes', 0)
            ->assertJsonPath('data.viewer_vote', 0);

        $this->assertSame(1, CommunityDiscussionVote::count());
    }

    public function test_authenticated_user_can_report_discussion_content(): void
    {
        $discussion = CommunityDiscussion::create([
            'author_name' => 'AlexM',
            'title' => 'Report topic',
            'slug' => 'report-topic',
            'body' => 'Testing reports.',
            'status' => 'published',
            'last_reply_at' => now(),
        ]);
        $reply = $discussion->replies()->create([
            'author_name' => 'Jason93',
            'body' => 'Useful context.',
        ]);

        $reportResponse = $this->postJson("/api/v1/community/discussion-replies/{$reply->id}/report", [
            'reason' => 'source-discussion',
            'details' => 'Mentions a source directly.',
        ]);

        $reportResponse->assertCreated()
            ->assertJsonPath('message', 'Report submitted for moderator review.');

        $this->assertSame(1, CommunityDiscussionReport::count());
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
