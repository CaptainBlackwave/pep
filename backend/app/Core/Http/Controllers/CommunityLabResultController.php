<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityLabResultResource;
use App\Core\Models\CommunityLabResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityLabResultController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'compound_type' => ['nullable', 'string', 'max:80'],
            'compound' => ['nullable', 'string', 'max:160'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'lab' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = CommunityLabResult::query()
            ->with('submittedBy')
            ->where('status', 'published');

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

        if (!empty($validated['compound_type'])) {
            $query->where('compound_type', $validated['compound_type']);
        }

        if (!empty($validated['compound'])) {
            $query->where('compound_name', $validated['compound']);
        }

        if (!empty($validated['vendor'])) {
            $query->where('vendor_name', $validated['vendor']);
        }

        if (!empty($validated['lab'])) {
            $query->where('lab_name', $validated['lab']);
        }

        $limit = (int) ($validated['limit'] ?? 25);

        return CommunityLabResultResource::collection(
            $query->orderByDesc('tested_at')->orderByDesc('created_at')->limit($limit)->get()
        )->additional([
            'meta' => $this->meta(),
        ]);
    }

    public function show(string $result)
    {
        $labResult = $this->findPublishedResult($result)->load('submittedBy');
        $labResult->increment('views_count');
        $labResult->refresh()->load('submittedBy');

        return new CommunityLabResultResource($labResult);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'compound_name' => ['required', 'string', 'max:160'],
            'compound_type' => ['nullable', 'string', 'max:80'],
            'use_case' => ['nullable', 'string', 'max:120'],
            'vendor_name' => ['required', 'string', 'max:160'],
            'batch_code' => ['required', 'string', 'max:120'],
            'lab_name' => ['required', 'string', 'max:160'],
            'tested_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'report_id' => ['nullable', 'string', 'max:120'],
            'sample_type' => ['nullable', 'string', 'max:80'],
            'sample_condition' => ['nullable', 'string', 'max:80'],
            'purity_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'water_content_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'peptide_content_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'identity_result' => ['nullable', 'string', 'max:80'],
            'coa_filename' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $user = $request->user();
        $labResult = CommunityLabResult::create([
            ...$validated,
            'submitted_by_user_id' => $user?->id,
            'submitted_by_name' => null,
            'slug' => $this->uniqueSlug($validated['compound_name'], $validated['batch_code']),
            'overall_result' => 'Pending Review',
            'status' => 'pending',
            'is_verified' => false,
        ]);

        return (new CommunityLabResultResource($labResult->load('submittedBy')))
            ->response()
            ->setStatusCode(201);
    }

    private function findPublishedResult(string $value): CommunityLabResult
    {
        return CommunityLabResult::query()
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function uniqueSlug(string $compoundName, string $batchCode): string
    {
        $base = Str::slug("{$compoundName} {$batchCode}") ?: 'lab-result';
        $slug = $base;
        $suffix = 2;

        while (CommunityLabResult::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function meta(): array
    {
        $published = CommunityLabResult::where('status', 'published');

        return [
            'stats' => [
                'total' => (clone $published)->count(),
                'batches' => (clone $published)->distinct('batch_code')->count('batch_code'),
                'avg_purity' => round((float) (clone $published)->avg('purity_percent'), 1),
                'labs' => (clone $published)->distinct('lab_name')->count('lab_name'),
            ],
            'filters' => [
                'compound_types' => CommunityLabResult::where('status', 'published')->whereNotNull('compound_type')->distinct()->orderBy('compound_type')->pluck('compound_type')->filter()->values(),
                'compounds' => CommunityLabResult::where('status', 'published')->distinct()->orderBy('compound_name')->pluck('compound_name')->filter()->values(),
                'vendors' => CommunityLabResult::where('status', 'published')->distinct()->orderBy('vendor_name')->pluck('vendor_name')->filter()->values(),
                'labs' => CommunityLabResult::where('status', 'published')->distinct()->orderBy('lab_name')->pluck('lab_name')->filter()->values(),
            ],
        ];
    }
}
