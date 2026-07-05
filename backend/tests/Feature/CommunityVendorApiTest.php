<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityVendor;
use App\Core\Models\CommunityVendorClaim;
use App\Core\Models\CommunityVendorProduct;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_private_vendor_detail_includes_published_product_catalog(): void
    {
        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'status' => 'published',
        ]);

        CommunityVendorProduct::create([
            'vendor_id' => $vendor->id,
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'category' => 'Peptide',
            'strength' => '10mg',
            'price' => 85.00,
            'availability' => 'in_stock',
            'status' => 'published',
        ]);

        CommunityVendorProduct::create([
            'vendor_id' => $vendor->id,
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/vendors/purepeptides');

        $response->assertOk()
            ->assertJsonPath('data.product_count', 1)
            ->assertJsonPath('data.products.0.name', 'Retatrutide')
            ->assertJsonPath('data.products.0.price_label', '$85.00')
            ->assertJsonPath('data.top_products.0.slug', 'retatrutide')
            ->assertJsonMissingPath('data.products.1');
    }

    public function test_authenticated_user_can_submit_published_vendor_review(): void
    {
        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'review_count' => 1,
            'average_rating' => 3.00,
            'would_buy_again_percent' => 0,
            'status' => 'published',
        ]);
        $vendor->reviews()->create([
            'author_name' => 'ExistingReviewer',
            'title' => 'Mixed experience',
            'body' => 'Older published review.',
            'rating' => 3,
            'would_buy_again' => false,
            'status' => 'published',
            'reviewed_at' => '2025-05-20',
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
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.author.name', 'Reviewer');

        $this->assertDatabaseHas('community_vendor_reviews', [
            'vendor_id' => $vendor->id,
            'title' => 'Clean batch and fast shipping',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('community_vendors', [
            'id' => $vendor->id,
            'review_count' => 2,
            'average_rating' => 4.00,
            'would_buy_again_percent' => 50.00,
        ]);
    }

    public function test_authenticated_user_can_submit_vendor_review_with_photos(): void
    {
        Storage::fake('public');

        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'status' => 'published',
        ]);
        $user = User::factory()->create([
            'username' => 'Reviewer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->post("/api/v1/community/vendors/{$vendor->slug}/reviews", [
            'rating' => 5,
            'title' => 'Photo-backed review',
            'body' => 'The packaging and product condition matched expectations.',
            'photos' => [
                UploadedFile::fake()->image('review-photo.jpg', 640, 480),
            ],
            'would_buy_again' => true,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Photo-backed review');

        $photoUrl = $response->json('data.photo_urls.0');
        $this->assertStringStartsWith('http://localhost/storage/vendor-review-photos/', $photoUrl);
        Storage::disk('public')->assertExists(Str::after($photoUrl, '/storage/'));
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

    public function test_approved_vendor_user_can_create_and_edit_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'VendorUser',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/vendor-profile', [
            'name' => 'Frontend Vendor',
        ])->assertForbidden();

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');
        $this->withoutMiddleware(VerifyLicense::class);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/v1/admin/users/{$user->id}/vendor-access", [
            'approved' => true,
        ])->assertOk()
            ->assertJsonPath('message', 'Vendor access approved.')
            ->assertJsonPath('user.is_approved_vendor', true);

        Sanctum::actingAs($user->fresh());

        $response = $this->postJson('/api/v1/community/vendor-profile', [
            'name' => 'Frontend Vendor',
            'description' => 'Public vendor profile managed outside the admin panel.',
            'website_url' => 'https://vendor.example',
            'image_url' => 'https://vendor.example/logo.png',
            'contact_email' => 'support@vendor.example',
            'contact_telegram' => '@vendorhandle',
            'tags' => ['Domestic', 'Lab Tested'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Frontend Vendor')
            ->assertJsonPath('data.owner_user_id', $user->id)
            ->assertJsonPath('data.claim_status', 'verified')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_owned_by_viewer', true)
            ->assertJsonPath('data.image_url', 'https://vendor.example/logo.png')
            ->assertJsonPath('data.contact.email', 'support@vendor.example')
            ->assertJsonPath('data.tags.0', 'Domestic');

        $this->assertDatabaseHas('community_vendors', [
            'owner_user_id' => $user->id,
            'slug' => 'frontend-vendor',
            'status' => 'published',
            'claim_status' => 'verified',
            'contact_email' => 'support@vendor.example',
            'image_url' => 'https://vendor.example/logo.png',
        ]);

        $update = $this->patchJson('/api/v1/community/vendor-profile', [
            'support_url' => 'https://vendor.example/support',
            'response_policy' => 'Support replies within one business day.',
            'public_contact_notes' => 'Use support for profile questions.',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.contact.support_url', 'https://vendor.example/support')
            ->assertJsonPath('data.contact.response_policy', 'Support replies within one business day.');

        $this->assertDatabaseHas('community_vendors', [
            'owner_user_id' => $user->id,
            'status' => 'published',
            'claim_status' => 'verified',
            'support_url' => 'https://vendor.example/support',
            'response_policy' => 'Support replies within one business day.',
        ]);
    }

    public function test_approved_vendor_user_can_upload_vendor_profile_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'VendorUser',
            'is_approved_vendor' => true,
        ]);
        $vendor = CommunityVendor::create([
            'name' => 'Frontend Vendor',
            'slug' => 'frontend-vendor',
            'owner_user_id' => $user->id,
            'status' => 'published',
            'claim_status' => 'verified',
        ]);

        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/community/vendor-profile/image', [
            'image' => UploadedFile::fake()->image('vendor-logo.png', 300, 300),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.id', $vendor->id);

        $imageUrl = $response->json('data.image_url');
        $this->assertStringStartsWith('http://localhost/storage/vendor-images/', $imageUrl);
        Storage::disk('public')->assertExists(Str::after($imageUrl, '/storage/'));

        $this->assertSame($imageUrl, $vendor->fresh()->image_url);
    }

    public function test_approved_vendor_user_can_manage_product_catalog(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'VendorUser',
            'is_approved_vendor' => true,
        ]);
        $vendor = CommunityVendor::create([
            'name' => 'Frontend Vendor',
            'slug' => 'frontend-vendor',
            'owner_user_id' => $user->id,
            'status' => 'published',
            'claim_status' => 'verified',
        ]);

        Sanctum::actingAs($user);

        $create = $this->post('/api/v1/community/vendor-profile/products', [
            'name' => 'Retatrutide',
            'category' => 'Peptide',
            'strength' => '10mg',
            'package_size' => '1 vial',
            'purity_label' => '>98%',
            'description' => 'Research catalog listing only.',
            'price' => '85.00',
            'currency_code' => 'USD',
            'availability' => 'in_stock',
            'tags' => ['GLP-1', 'Research'],
            'image' => UploadedFile::fake()->image('retatrutide.png', 400, 400),
        ], ['Accept' => 'application/json']);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Retatrutide')
            ->assertJsonPath('data.price_label', '$85.00')
            ->assertJsonPath('data.availability_label', 'In stock');

        $productId = $create->json('data.id');
        $imageUrl = $create->json('data.image_url');
        $this->assertStringStartsWith('http://localhost/storage/vendor-product-images/', $imageUrl);
        Storage::disk('public')->assertExists(Str::after($imageUrl, '/storage/'));

        $update = $this->patchJson("/api/v1/community/vendor-profile/products/{$productId}", [
            'price' => '79.50',
            'availability' => 'limited',
            'status' => 'published',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.price_label', '$79.50')
            ->assertJsonPath('data.availability', 'limited');

        $this->assertDatabaseHas('community_vendor_products', [
            'vendor_id' => $vendor->id,
            'name' => 'Retatrutide',
            'price' => 79.50,
            'availability' => 'limited',
        ]);

        $this->deleteJson("/api/v1/community/vendor-profile/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('message', 'Product removed.');

        $this->assertDatabaseMissing('community_vendor_products', [
            'id' => $productId,
        ]);
    }

    public function test_admin_can_toggle_vendor_access_from_user_management(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');
        $user = User::factory()->create([
            'username' => 'VendorUser',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/users/{$user->id}/vendor-access", [
            'approved' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Vendor access approved.')
            ->assertJsonPath('user.is_approved_vendor', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_approved_vendor' => true,
        ]);

        $response = $this->patchJson("/api/v1/admin/users/{$user->id}/vendor-access", [
            'approved' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Vendor access revoked.')
            ->assertJsonPath('user.is_approved_vendor', false);
    }

    public function test_authenticated_user_can_request_claim_for_existing_unowned_vendor(): void
    {
        $user = User::factory()->create([
            'username' => 'Claimant',
        ]);
        $vendor = CommunityVendor::create([
            'name' => 'Existing Vendor',
            'slug' => 'existing-vendor',
            'status' => 'published',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/community/vendors/{$vendor->slug}/claim", [
            'message' => 'I can verify control of this public vendor profile.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Claim request submitted.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.vendor.slug', 'existing-vendor');

        $this->assertDatabaseHas('community_vendor_claims', [
            'vendor_id' => $vendor->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'message' => 'I can verify control of this public vendor profile.',
        ]);
        $this->assertDatabaseHas('community_vendors', [
            'id' => $vendor->id,
            'owner_user_id' => null,
            'claim_status' => 'pending',
        ]);
    }

    public function test_admin_can_hide_published_vendor_review(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $vendor = CommunityVendor::create([
            'name' => 'PurePeptides',
            'slug' => 'purepeptides',
            'review_count' => 1,
            'average_rating' => 5.00,
            'would_buy_again_percent' => 100,
            'status' => 'published',
        ]);
        $review = $vendor->reviews()->create([
            'author_name' => 'Reviewer',
            'title' => 'Published review',
            'body' => 'Visible review.',
            'rating' => 5,
            'would_buy_again' => true,
            'status' => 'published',
        ]);
        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/community/vendor-reviews/{$review->id}", [
            'status' => 'hidden',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'hidden');

        $this->assertDatabaseHas('community_vendor_reviews', [
            'id' => $review->id,
            'status' => 'hidden',
        ]);
        $this->assertDatabaseHas('community_vendors', [
            'id' => $vendor->id,
            'review_count' => 0,
            'average_rating' => 0,
            'would_buy_again_percent' => 0,
        ]);
    }
}
