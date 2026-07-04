<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityContentItem;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_research_index_returns_published_items_and_meta(): void
    {
        CommunityContentItem::create([
            'type' => 'research',
            'title' => 'Retatrutide Review',
            'slug' => 'retatrutide',
            'category' => 'Peptides',
            'category_slug' => 'peptides',
            'tag' => 'Peptides',
            'excerpt' => 'Research summary',
            'body' => 'Research body',
            'views_count' => 1200,
            'downloads_count' => 342,
            'status' => 'published',
            'published_at' => now(),
            'metadata' => ['compound' => 'Retatrutide (RETA)'],
        ]);

        CommunityContentItem::create([
            'type' => 'research',
            'title' => 'Hidden Research',
            'slug' => 'hidden-research',
            'category' => 'Peptides',
            'category_slug' => 'peptides',
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/research-library');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'retatrutide')
            ->assertJsonPath('meta.categories.0.slug', 'peptides')
            ->assertJsonPath('meta.topics.0.name', 'Peptides')
            ->assertJsonPath('meta.filters.tags.0', 'Peptides')
            ->assertJsonPath('meta.filters.compounds.0', 'Retatrutide (RETA)')
            ->assertJsonPath('meta.filters.sorts.0.value', 'latest');
    }

    public function test_private_research_index_filters_by_category_tag_and_compound(): void
    {
        CommunityContentItem::create([
            'type' => 'research',
            'title' => 'Retatrutide Review',
            'slug' => 'retatrutide',
            'category' => 'Peptides',
            'category_slug' => 'peptides',
            'tag' => 'Peptides',
            'excerpt' => 'Research summary',
            'body' => 'Research body',
            'views_count' => 1200,
            'status' => 'published',
            'published_at' => '2025-05-28 09:00:00',
            'metadata' => ['compound' => 'Retatrutide (RETA)'],
        ]);

        CommunityContentItem::create([
            'type' => 'research',
            'title' => 'SARMs Review',
            'slug' => 'sarms-review',
            'category' => 'SARMs',
            'category_slug' => 'sarms',
            'tag' => 'SARMs',
            'excerpt' => 'SARM summary',
            'body' => 'SARM body',
            'views_count' => 1500,
            'status' => 'published',
            'published_at' => '2025-05-21 09:00:00',
            'metadata' => ['compound' => 'Example SARM'],
        ]);

        $response = $this->getJson('/api/v1/community/research-library?category=peptides&tag=Peptides&compound=Retatrutide%20(RETA)&sort=popular');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'retatrutide')
            ->assertJsonMissingPath('data.1');
    }

    public function test_private_content_detail_increments_views(): void
    {
        $item = CommunityContentItem::create([
            'type' => 'guide',
            'title' => 'Storage Guide',
            'slug' => 'storage-guide',
            'category' => 'Storage',
            'category_slug' => 'storage',
            'body' => 'Guide body',
            'views_count' => 9,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/community/guides/storage-guide');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'storage-guide')
            ->assertJsonPath('data.views', 10);

        $this->assertDatabaseHas('community_content_items', [
            'id' => $item->id,
            'views_count' => 10,
        ]);
    }

    public function test_admin_can_create_published_content_item(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create(['username' => 'AdminUser']);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/community/content', [
            'type' => 'faq',
            'title' => 'How should I store peptides?',
            'category' => 'Storage',
            'excerpt' => 'Storage answer',
            'body' => 'Store according to documented guidance.',
            'status' => 'published',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'faq')
            ->assertJsonPath('data.slug', 'how-should-i-store-peptides')
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('community_content_items', [
            'type' => 'faq',
            'slug' => 'how-should-i-store-peptides',
            'status' => 'published',
        ]);
    }
}
