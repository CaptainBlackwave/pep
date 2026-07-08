<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityContentItem;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommunityContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadContentLibraryRoutesForTests();
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

    public function test_staff_can_create_frontend_content_draft_without_admin_access(): void
    {
        $this->seedContentContributorPermissions();

        $staff = User::factory()->create(['username' => 'StaffUser']);
        $staff->assignRole('staff');

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/community/content', [
            'type' => 'guide',
            'title' => 'Frontend Storage Guide',
            'category' => 'Storage',
            'excerpt' => 'A staff-created draft.',
            'body' => 'Draft body.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'guide')
            ->assertJsonPath('data.slug', 'frontend-storage-guide')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('community_content_items', [
            'slug' => 'frontend-storage-guide',
            'user_id' => $staff->id,
            'status' => 'draft',
        ]);
    }

    public function test_staff_cannot_publish_frontend_content(): void
    {
        $this->seedContentContributorPermissions();

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/community/content', [
            'type' => 'research',
            'title' => 'Pending Research',
            'category' => 'Research',
            'status' => 'published',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('community_content_items', [
            'slug' => 'pending-research',
            'status' => 'published',
        ]);
    }

    public function test_content_editor_can_publish_from_frontend_without_admin_panel_permission(): void
    {
        $this->seedContentContributorPermissions();

        $editor = User::factory()->create(['username' => 'ContentEditor']);
        $editor->assignRole('content-editor');

        Sanctum::actingAs($editor);

        $response = $this->postJson('/api/v1/community/content', [
            'type' => 'faq',
            'title' => 'Can staff publish FAQs?',
            'category' => 'Workflow',
            'body' => 'Only content editors can publish directly.',
            'status' => 'published',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'faq')
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('community_content_items', [
            'slug' => 'can-staff-publish-faqs',
            'status' => 'published',
            'user_id' => $editor->id,
        ]);

        $this->assertFalse($editor->fresh()->can('view admin panel'));
    }

    private function seedContentContributorPermissions(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'community-content.create',
            'community-content.update',
            'community-content.publish',
            'community-content.manage',
            'view admin panel',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'sanctum'])
            ->syncPermissions(['community-content.create', 'community-content.update']);

        Role::firstOrCreate(['name' => 'content-editor', 'guard_name' => 'sanctum'])
            ->syncPermissions([
                'community-content.create',
                'community-content.update',
                'community-content.publish',
                'community-content.manage',
            ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function loadContentLibraryRoutesForTests(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace('App\\Plugins\\ContentLibrary\\Controllers')
            ->group(base_path('app/Plugins/content-library/routes/api.php'));

        Route::prefix('api/v1/admin')
            ->middleware(['api', 'auth:sanctum', 'role:admin|moderator', 'verify.license'])
            ->namespace('App\\Plugins\\ContentLibrary\\Controllers\\Admin')
            ->name('admin.')
            ->group(base_path('app/Plugins/content-library/routes/admin.php'));
    }
}
