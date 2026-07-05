<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityNotification;
use App\Core\Models\CommunityNotificationRead;
use App\Core\Models\Notification;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_notification_index_returns_published_notifications_and_stats(): void
    {
        CommunityNotification::create([
            'title' => 'Published notice',
            'slug' => 'published-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'excerpt' => 'Published summary',
            'body' => 'Published body',
            'status' => 'published',
            'is_pinned' => true,
            'views_count' => 12,
            'published_at' => now(),
        ]);

        CommunityNotification::create([
            'title' => 'Hidden notice',
            'slug' => 'hidden-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/notifications');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'published-notice')
            ->assertJsonPath('data.0.unread', true)
            ->assertJsonPath('meta.stats.total', 1)
            ->assertJsonPath('meta.stats.announcements', 1)
            ->assertJsonPath('meta.categories.0.slug', 'announcements');
    }

    public function test_notification_detail_increments_views(): void
    {
        $notification = CommunityNotification::create([
            'title' => 'Lab notice',
            'slug' => 'lab-notice',
            'category' => 'Lab Results',
            'category_slug' => 'lab-results',
            'icon' => 'flask',
            'tone' => 'blue',
            'status' => 'published',
            'views_count' => 4,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/community/notifications/lab-notice');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'lab-notice')
            ->assertJsonPath('data.views', 5);

        $this->assertDatabaseHas('community_notifications', [
            'id' => $notification->id,
            'views_count' => 5,
        ]);
    }

    public function test_authenticated_user_can_mark_notification_read(): void
    {
        $user = User::factory()->create();
        $notification = CommunityNotification::create([
            'title' => 'Unread notice',
            'slug' => 'unread-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/community/notifications/unread-notice/read');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'unread-notice')
            ->assertJsonPath('data.unread', false);

        $this->assertDatabaseHas('community_notification_reads', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_notification_index_reports_user_read_state(): void
    {
        $user = User::factory()->create();
        $readNotification = CommunityNotification::create([
            'title' => 'Read notice',
            'slug' => 'read-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'status' => 'published',
            'published_at' => now(),
        ]);
        CommunityNotification::create([
            'title' => 'Unread notice',
            'slug' => 'still-unread-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'status' => 'published',
            'published_at' => now()->addMinute(),
        ]);
        CommunityNotificationRead::create([
            'notification_id' => $readNotification->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/community/notifications');

        $response->assertOk()
            ->assertJsonPath('meta.stats.unread', 1)
            ->assertJsonPath('data.0.slug', 'still-unread-notice')
            ->assertJsonPath('data.0.unread', true);
    }

    public function test_authenticated_user_can_mark_all_notifications_read(): void
    {
        $user = User::factory()->create();

        foreach ([
            [
                'title' => 'First notice',
                'slug' => 'first-notice',
                'category' => 'Announcements',
                'category_slug' => 'announcements',
                'icon' => 'megaphone',
                'tone' => 'purple',
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'title' => 'Second notice',
                'slug' => 'second-notice',
                'category' => 'Lab Results',
                'category_slug' => 'lab-results',
                'icon' => 'flask',
                'tone' => 'blue',
                'status' => 'published',
                'published_at' => now(),
            ],
        ] as $attributes) {
            CommunityNotification::create($attributes);
        }

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/community/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('read_count', 2);

        $this->assertSame(2, CommunityNotificationRead::where('user_id', $user->id)->count());
    }

    public function test_personal_reply_mention_and_message_notifications_behave_like_normal_notifications(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'type' => 'discussion_reply',
            'title' => 'New reply',
            'message' => 'Someone replied to a discussion you follow.',
            'link' => '/discussions/reply-topic',
        ]);
        $mention = Notification::create([
            'user_id' => $user->id,
            'type' => 'discussion_mention',
            'title' => 'You were mentioned',
            'message' => 'A member mentioned you in a discussion.',
            'link' => '/discussions/mention-topic',
        ]);
        Notification::create([
            'user_id' => $user->id,
            'type' => 'message',
            'title' => 'New Message',
            'message' => 'You have a new private message.',
            'link' => '/messages?thread=10',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/community/notifications')
            ->assertOk()
            ->assertJsonPath('meta.stats.unread', 3)
            ->assertJsonPath('meta.stats.replies_unread', 1)
            ->assertJsonPath('meta.stats.mentions_unread', 1)
            ->assertJsonPath('meta.stats.messages_unread', 1);

        $this->getJson('/api/v1/community/notifications?category=mentions')
            ->assertOk()
            ->assertJsonPath('data.0.category_slug', 'mentions')
            ->assertJsonPath('data.0.href', '/discussions/mention-topic');

        $this->postJson("/api/v1/community/notifications/personal_{$mention->id}/read")
            ->assertOk()
            ->assertJsonPath('data.slug', "personal_{$mention->id}")
            ->assertJsonPath('data.unread', false);

        $this->assertDatabaseHas('notifications', [
            'id' => $mention->id,
        ]);
        $this->assertNotNull($mention->fresh()->read_at);
    }

    public function test_admin_can_create_published_notification(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/community/notifications', [
            'title' => 'New lab report available',
            'category' => 'Lab Results',
            'icon' => 'flask',
            'tone' => 'blue',
            'excerpt' => 'A new lab report has been published.',
            'body' => 'The report is available for review.',
            'source_url' => '/lab-results/new-report',
            'status' => 'published',
            'is_pinned' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'new-lab-report-available')
            ->assertJsonPath('data.category_slug', 'lab-results')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_pinned', true);

        $this->assertDatabaseHas('community_notifications', [
            'slug' => 'new-lab-report-available',
            'status' => 'published',
            'is_pinned' => true,
        ]);
    }

    public function test_admin_can_hide_notification(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $notification = CommunityNotification::create([
            'title' => 'Hide notice',
            'slug' => 'hide-notice',
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/community/notifications/{$notification->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('community_notifications', [
            'id' => $notification->id,
            'status' => 'hidden',
        ]);
    }
}
