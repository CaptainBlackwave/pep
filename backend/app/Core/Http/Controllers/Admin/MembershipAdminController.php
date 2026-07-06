<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\MembershipPlan;
use App\Core\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipAdminController extends Controller
{
    public function settings(): JsonResponse
    {
        $enabled = Setting::where('key', 'membership_enabled')->value('value');

        return response()->json([
            'membership_enabled' => $enabled === '1',
            'plans' => MembershipPlan::orderBy('sort_order')->get()->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'features' => $plan->features ?? [],
                'active' => $plan->active,
                'sort_order' => $plan->sort_order,
            ]),
        ]);
    }

    public function toggle(): JsonResponse
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'membership_enabled'],
            ['value' => '0', 'type' => 'boolean']
        );

        $newValue = $setting->value === '1' ? '0' : '1';
        $setting->update(['value' => $newValue]);

        return response()->json([
            'success' => true,
            'membership_enabled' => $newValue === '1',
        ]);
    }

    public function updatePlan(Request $request, MembershipPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'features' => $plan->features ?? [],
                'active' => $plan->active,
                'sort_order' => $plan->sort_order,
            ],
        ]);
    }
}
