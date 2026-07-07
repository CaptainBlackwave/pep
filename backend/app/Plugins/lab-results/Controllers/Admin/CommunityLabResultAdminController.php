<?php

namespace App\Plugins\LabResults\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityLabResultResource;
use App\Core\Models\CommunityLabResult;
use App\Core\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityLabResultAdminController extends Controller
{
    public function __construct(
        private PushNotificationService $push,
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityLabResult::query()->with('submittedBy');

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('compound_name', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('batch_code', 'like', "%{$search}%")
                    ->orWhere('lab_name', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityLabResultResource::collection(
            $query->latest('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityLabResult::count(),
                    'pending' => CommunityLabResult::where('status', 'pending')->count(),
                    'published' => CommunityLabResult::where('status', 'published')->count(),
                    'hidden' => CommunityLabResult::where('status', 'hidden')->count(),
                    'verified' => CommunityLabResult::where('is_verified', true)->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request, string $result)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'published', 'hidden'])],
            'is_verified' => ['nullable', 'boolean'],
            'overall_result' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $labResult = $this->findResult($result);
        $labResult->fill($validated);

        if (($validated['status'] ?? null) === 'published' && !array_key_exists('is_verified', $validated)) {
            $labResult->is_verified = true;
        }

        if (($validated['status'] ?? null) === 'published' && empty($labResult->overall_result)) {
            $labResult->overall_result = 'Pass';
        }

        $wasPublished = $labResult->getOriginal('status') === 'published';
        $labResult->save();

        if (!$wasPublished && $labResult->status === 'published') {
            $compound = $labResult->compound_name;
            $this->push->notifyAllPremium(
                'lab_result_added',
                "New lab result: {$compound}",
                "A new lab result for {$compound} has been published.",
                "/lab-results/{$labResult->slug}",
            );
        }

        return new CommunityLabResultResource($labResult->load('submittedBy'));
    }

    public function destroy(string $result)
    {
        $labResult = $this->findResult($result);
        $labResult->forceFill([
            'status' => 'hidden',
            'is_verified' => false,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Lab result hidden.',
        ]);
    }

    private function findResult(string $value): CommunityLabResult
    {
        return CommunityLabResult::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }
}
