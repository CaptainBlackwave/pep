<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityAnnouncement;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityAnnouncementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_community_announcements_are_public(): void
    {
        auth()->guard('sanctum')->forgetUser();

        $this->getJson('/api/v1/community/announcements')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_private_announcement_index_returns_published_announcements_and_stats(): void
    {
        CommunityAnnouncement::create([
            'title' => 'Pinned announcement',
            'slug' => 'pinned-announcement',
            'category' => 'Platform Update',
            'category_slug' => 'platform-update',
            'excerpt' => 'Pinned summary',
            'body' => 'Pinned body',
            'is_pinned' => true,
            'status' => 'published',
            'views_count' => 100,
            'comments_count' => 5,
            'published_at' => now(),
        ]);

        CommunityAnnouncement::create([
            'title' => 'Hidden announcement',
            'slug' => 'hidden-announcement',
            'body' => 'Hidden body',
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/announcements');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'pinned-announcement')
            ->assertJsonPath('data.0.is_pinned', true)
            ->assertJsonPath('meta.stats.total', 1)
            ->assertJsonPath('meta.stats.total_views', 100)
            ->assertJsonPath('meta.categories.0.slug', 'platform-update');
    }

    public function test_private_announcement_detail_increments_views(): void
    {
        $announcement = CommunityAnnouncement::create([
            'title' => 'Welcome',
            'slug' => 'welcome',
            'body' => 'Welcome body',
            'status' => 'published',
            'views_count' => 7,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/community/announcements/welcome');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'welcome')
            ->assertJsonPath('data.views', 8);

        $this->assertDatabaseHas('community_announcements', [
            'id' => $announcement->id,
            'views_count' => 8,
        ]);
    }

    public function test_admin_can_create_published_announcement(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/community/announcements', [
            'title' => 'New Lab Testing Report Available',
            'category' => 'Platform Update',
            'icon' => 'megaphone',
            'tone' => 'purple',
            'excerpt' => 'A new lab test report has been published.',
            'body' => 'A new lab test report has been published and verified.',
            'status' => 'published',
            'is_pinned' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'new-lab-testing-report-available')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_pinned', true)
            ->assertJsonPath('data.author.name', 'AdminUser');

        $this->assertDatabaseHas('community_announcements', [
            'slug' => 'new-lab-testing-report-available',
            'status' => 'published',
            'is_pinned' => true,
        ]);
    }

    public function test_admin_can_hide_announcement(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $announcement = CommunityAnnouncement::create([
            'title' => 'Maintenance',
            'slug' => 'maintenance',
            'body' => 'Maintenance body',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/admin/community/announcements/{$announcement->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('community_announcements', [
            'id' => $announcement->id,
            'status' => 'hidden',
        ]);
    }
}
