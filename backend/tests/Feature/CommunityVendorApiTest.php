<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityVendor;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityVendorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_vendor_index_returns_published_vendors_and_stats(): void
    {
        CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'logo_initials' => 'PP',
            'status_label' => 'Trusted',
            'status_class' => 'trusted',
            'description' => 'Trusted vendor',
            'review_count' => 125,
            'average_rating' => 4.80,
            'would_buy_again_percent' => 92,
            'tags' => ['Fast Shipping', 'Lab Tested'],
            'status' => 'published',
        ]);

        CommunityVendor::create([
            'name' => 'Hidden Vendor',
            'slug' => 'hidden-vendor',
            'review_count' => 5,
            'average_rating' => 2.00,
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/vendors');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'purepeptides')
            ->assertJsonPath('data.0.rating_label', '4.8')
            ->assertJsonPath('meta.stats.vendors_reviewed', 1)
            ->assertJsonPath('meta.stats.total_reviews', 125)
            ->assertJsonPath('meta.filters.statuses.0.slug', 'trusted')
            ->assertJsonPath('meta.filters.ratings.0', 4)
            ->assertJsonPath('meta.filters.tags.0', 'Fast Shipping');
    }

    public function test_private_vendor_index_filters_by_status_rating_and_tag(): void
    {
        CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'status_label' => 'Trusted',
            'status_class' => 'trusted',
            'review_count' => 125,
            'average_rating' => 4.80,
            'tags' => ['Fast Shipping', 'Lab Tested'],
            'status' => 'published',
        ]);

        CommunityVendor::create([
            'name' => 'AlphaChem',
            'slug' => 'alphachem',
            'status_label' => 'Caution',
            'status_class' => 'caution',
            'review_count' => 43,
            'average_rating' => 3.20,
            'tags' => ['Slow Shipping'],
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/community/vendors?status=trusted&rating_min=4&tag=Lab%20Tested');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'purepeptides')
            ->assertJsonMissingPath('data.1');
    }

    public function test_private_vendor_detail_includes_published_reviews(): void
    {
        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'review_count' => 125,
            'average_rating' => 4.80,
            'status' => 'published',
        ]);

        $vendor->reviews()->create([
            'author_name' => 'Jason93',
            'title' => 'Excellent quality',
            'body' => 'Great experience overall.',
            'rating' => 5,
            'product_name' => 'Retatrutide',
            'status' => 'published',
            'is_verified_buyer' => true,
            'reviewed_at' => '2025-05-20',
        ]);

        $vendor->reviews()->create([
            'author_name' => 'PendingUser',
            'title' => 'Pending review',
            'body' => 'Not yet moderated.',
            'rating' => 3,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/community/vendors/purepeptides');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'purepeptides')
            ->assertJsonPath('data.review_items.0.title', 'Excellent quality')
            ->assertJsonMissingPath('data.review_items.1');
    }

    public function test_authenticated_user_can_submit_pending_vendor_review(): void
    {
        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'status' => 'published',
        ]);
        $user = User::factory()->create([
            'username' => 'Reviewer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/community/vendors/{$vendor->slug}/reviews", [
            'rating' => 5,
            'title' => 'Clean batch and fast shipping',
            'body' => 'The batch matched the COA and arrived quickly.',
            'product_name' => 'Retatrutide',
            'tags' => ['Fast Shipping', 'Lab Tested'],
            'would_buy_again' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.author.name', 'Reviewer');

        $this->assertDatabaseHas('community_vendor_reviews', [
            'vendor_id' => $vendor->id,
            'title' => 'Clean batch and fast shipping',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_create_vendor(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/community/vendors', [
            'name' => 'Test Vendor',
            'website_url' => 'https://vendor.example',
            'description' => 'A vendor created from the admin panel.',
            'status' => 'published',
            'status_label' => 'Caution',
            'status_class' => 'caution',
            'tags' => ['Domestic', 'Lab Tested'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Vendor')
            ->assertJsonPath('data.slug', 'test-vendor')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.status_class', 'caution')
            ->assertJsonPath('data.tags.0', 'Domestic');

        $this->assertDatabaseHas('community_vendors', [
            'name' => 'Test Vendor',
            'slug' => 'test-vendor',
            'status' => 'published',
            'status_class' => 'caution',
        ]);
    }

    public function test_authenticated_user_can_mark_published_vendor_review_helpful(): void
    {
        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'status' => 'published',
        ]);
        $review = $vendor->reviews()->create([
            'author_name' => 'Reviewer',
            'title' => 'Useful review',
            'body' => 'This helped me compare vendors.',
            'rating' => 5,
            'helpful_count' => 2,
            'status' => 'published',
        ]);

        $response = $this->postJson("/api/v1/community/vendor-reviews/{$review->id}/helpful");

        $response->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.helpful_count', 3);

        $this->assertDatabaseHas('community_vendor_reviews', [
            'id' => $review->id,
            'helpful_count' => 3,
        ]);
    }

    public function test_admin_can_publish_vendor_review(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'review_count' => 125,
            'average_rating' => 4.80,
            'status' => 'published',
        ]);
        $review = $vendor->reviews()->create([
            'author_name' => 'Reviewer',
            'title' => 'Pending review',
            'body' => 'Ready for moderation.',
            'rating' => 5,
            'status' => 'pending',
        ]);
        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/community/vendor-reviews/{$review->id}", [
            'status' => 'published',
            'is_verified_buyer' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_verified_buyer', true);

        $this->assertDatabaseHas('community_vendor_reviews', [
            'id' => $review->id,
            'status' => 'published',
            'is_verified_buyer' => true,
        ]);
        $this->assertDatabaseHas('community_vendors', [
            'id' => $vendor->id,
            'review_count' => 126,
        ]);
    }
}
