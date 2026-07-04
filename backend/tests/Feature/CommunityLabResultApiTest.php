<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityLabResult;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityLabResultApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_lab_result_index_returns_published_results_and_stats(): void
    {
        CommunityLabResult::create([
            'compound_name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'compound_type' => 'Peptide',
            'use_case' => 'Weight Loss',
            'vendor_name' => 'PurePeptides',
            'batch_code' => 'RETA-2405-02',
            'lab_name' => 'Janoshik',
            'tested_at' => '2025-05-23',
            'received_at' => '2025-05-19',
            'purity_percent' => 98.70,
            'identity_result' => 'Conforms',
            'overall_result' => 'Pass',
            'status' => 'published',
            'is_verified' => true,
        ]);

        CommunityLabResult::create([
            'compound_name' => 'Hidden Report',
            'slug' => 'hidden-report',
            'vendor_name' => 'Hidden Vendor',
            'batch_code' => 'HIDE-01',
            'lab_name' => 'Hidden Lab',
            'status' => 'hidden',
        ]);

        $response = $this->getJson('/api/v1/community/lab-results');

        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'retatrutide')
            ->assertJsonPath('data.0.purity', '98.7%')
            ->assertJsonPath('meta.stats.total', 1)
            ->assertJsonPath('meta.stats.batches', 1)
            ->assertJsonPath('meta.filters.compounds.0', 'Retatrutide')
            ->assertJsonPath('meta.filters.vendors.0', 'PurePeptides');
    }

    public function test_private_lab_result_index_filters_by_compound_vendor_and_lab(): void
    {
        CommunityLabResult::create([
            'compound_name' => 'Compound A',
            'slug' => 'compound-a',
            'compound_type' => 'Peptide',
            'vendor_name' => 'Vendor A',
            'batch_code' => 'A-01',
            'lab_name' => 'Lab A',
            'status' => 'published',
            'is_verified' => true,
        ]);

        CommunityLabResult::create([
            'compound_name' => 'Compound B',
            'slug' => 'compound-b',
            'compound_type' => 'Peptide',
            'vendor_name' => 'Vendor B',
            'batch_code' => 'B-01',
            'lab_name' => 'Lab B',
            'status' => 'published',
            'is_verified' => true,
        ]);

        $response = $this->getJson('/api/v1/community/lab-results?compound=Compound%20A&vendor=Vendor%20A&lab=Lab%20A');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'compound-a');
    }

    public function test_private_lab_result_detail_increments_views(): void
    {
        CommunityLabResult::create([
            'compound_name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'vendor_name' => 'PurePeptides',
            'batch_code' => 'RETA-2405-02',
            'lab_name' => 'Janoshik',
            'tested_at' => '2025-05-23',
            'purity_percent' => 98.70,
            'status' => 'published',
            'is_verified' => true,
            'views_count' => 7,
        ]);

        $response = $this->getJson('/api/v1/community/lab-results/retatrutide');

        $response->assertOk()
            ->assertJsonPath('data.views', 8)
            ->assertJsonPath('data.compound_name', 'Retatrutide');
    }

    public function test_authenticated_user_can_submit_pending_lab_result(): void
    {
        $user = User::factory()->create([
            'name' => 'Lab User',
            'username' => 'LabUser',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/community/lab-results', [
            'compound_name' => 'BPC-157',
            'compound_type' => 'Peptide',
            'use_case' => 'Healing',
            'vendor_name' => 'BioElite',
            'batch_code' => 'BPC-0425',
            'lab_name' => 'Janoshik',
            'tested_at' => '2025-05-19',
            'purity_percent' => 97.80,
            'coa_filename' => 'bpc-157-coa.pdf',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.submitted_by.name', 'LabUser');

        $this->assertDatabaseHas('community_lab_results', [
            'compound_name' => 'BPC-157',
            'slug' => 'bpc-157-bpc-0425',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_publish_and_verify_lab_result(): void
    {
        $this->withoutMiddleware(VerifyLicense::class);

        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');

        CommunityLabResult::create([
            'compound_name' => 'MT-2',
            'slug' => 'mt-2',
            'vendor_name' => 'Research Chems',
            'batch_code' => 'MT2-0525',
            'lab_name' => 'MZ Biolabs',
            'status' => 'pending',
            'is_verified' => false,
            'overall_result' => 'Pending Review',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/v1/admin/community/lab-results/mt-2', [
            'status' => 'published',
            'is_verified' => true,
            'overall_result' => 'Pass',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.overall_result', 'Pass');

        $this->assertDatabaseHas('community_lab_results', [
            'slug' => 'mt-2',
            'status' => 'published',
            'is_verified' => true,
            'overall_result' => 'Pass',
        ]);
    }
}
