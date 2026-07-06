<?php

namespace Database\Seeders;

use App\Core\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        MembershipPlan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'description' => 'Full access to vendor reviews, lab results, messaging, member profiles, and unlimited discussions.',
            'price_monthly' => 9.99,
            'price_yearly' => 99.99,
            'features' => [
                'vendor_reviews',
                'lab_results',
                'messaging',
                'member_profiles',
                'unlimited_discussions',
                'file_uploads',
            ],
            'active' => true,
            'sort_order' => 1,
        ]);
    }
}
