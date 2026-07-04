<?php

namespace Tests\Feature;

use App\Core\Middleware\VerifyLicense;
use App\Core\Models\CommunityAccessCode;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityAccessCodeAdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->withoutMiddleware(VerifyLicense::class);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'username' => 'AdminUser',
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_can_generate_access_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/community/access-codes', [
            'label' => 'Approved member invite',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.label', 'Approved member invite')
            ->assertJsonPath('data.status', 'available')
            ->assertJsonStructure([
                'data' => ['id', 'code', 'label', 'status', 'created_at'],
            ]);

        $plainCode = $response->json('data.code');
        $storedCode = CommunityAccessCode::first();

        $this->assertNotEmpty($plainCode);
        $this->assertNotSame($plainCode, $storedCode->code_hash);
        $this->assertTrue(CommunityAccessCode::isUsableCode($plainCode));
    }

    public function test_admin_can_list_access_codes_with_stats(): void
    {
        $this->actingAsAdmin();

        CommunityAccessCode::create([
            'code_hash' => CommunityAccessCode::hashCode('PV-TEST-CODE-1111'),
            'label' => 'Open invite',
        ]);
        CommunityAccessCode::create([
            'code_hash' => CommunityAccessCode::hashCode('PV-USED-CODE-2222'),
            'label' => 'Used invite',
            'used_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/admin/community/access-codes');

        $response->assertOk()
            ->assertJsonPath('meta.stats.total', 2)
            ->assertJsonPath('meta.stats.available', 1)
            ->assertJsonPath('meta.stats.used', 1);
    }

    public function test_admin_can_revoke_unused_access_code(): void
    {
        $this->actingAsAdmin();

        $accessCode = CommunityAccessCode::create([
            'code_hash' => CommunityAccessCode::hashCode('PV-OPEN-CODE-3333'),
            'label' => 'Temporary invite',
        ]);

        $response = $this->deleteJson("/api/v1/admin/community/access-codes/{$accessCode->id}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $this->assertFalse(CommunityAccessCode::isUsableCode('PV-OPEN-CODE-3333'));
    }
}
