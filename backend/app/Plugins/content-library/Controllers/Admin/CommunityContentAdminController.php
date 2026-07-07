<?php

namespace App\Plugins\ContentLibrary\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityContentItemResource;
use App\Core\Models\CommunityContentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityContentAdminController extends Controller
{

    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['all', 'research', 'guide', 'faq'])],
            'status' => ['nullable', Rule::in(['all', 'draft', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityContentItem::query()->with('author');

        if (($validated['type'] ?? 'all') !== 'all') {
            $query->where('type', $validated['type']);
        }

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityContentItemResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityContentItem::count(),
                    'research' => CommunityContentItem::where('type', 'research')->count(),
                    'guides' => CommunityContentItem::where('type', 'guide')->count(),
                    'faqs' => CommunityContentItem::where('type', 'faq')->count(),
                    'published' => CommunityContentItem::where('status', 'published')->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateContent($request);
        $status = $validated['status'] ?? 'draft';

        $item = CommunityContentItem::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['type'], $validated['slug'] ?? $validated['title']),
            'category_slug' => $validated['category_slug'] ?? Str::slug($validated['category'] ?? 'General'),
            'user_id' => $request->user()?->id,
            'author_name' => $validated['author_name'] ?? null,
            'status' => $status,
            'published_at' => $validated['published_at'] ?? ($status === 'published' ? now() : null),
        ]);

        return (new CommunityContentItemResource($item->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $content)
    {
        $item = $this->findContent($content);
        $validated = $this->validateContent($request, true);

        if (isset($validated['slug']) || isset($validated['title'])) {
            $validated['slug'] = $this->uniqueSlug(
                $validated['type'] ?? $item->type,
                $validated['slug'] ?? $validated['title'] ?? $item->slug,
                $item->id
            );
        }

        if (isset($validated['category']) && empty($validated['category_slug'])) {
            $validated['category_slug'] = Str::slug($validated['category']);
        }

        if (($validated['status'] ?? null) === 'published' && !$item->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $item->fill($validated)->save();

        return new CommunityContentItemResource($item->load('author'));
    }

    public function destroy(string $content)
    {
        $item = $this->findContent($content);
        $item->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Content item hidden.',
        ]);
    }

    private function validateContent(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, Rule::in(['research', 'guide', 'faq'])],
            'title' => [$required, 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240'],
            'category' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:100'],
            'category_slug' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:80'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:50000'],
            'icon' => ['nullable', 'string', 'max:40'],
            'image_index' => ['nullable', 'integer', 'min:0', 'max:99'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'read_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'views_count' => ['nullable', 'integer', 'min:0'],
            'downloads_count' => ['nullable', 'integer', 'min:0'],
            'comments_count' => ['nullable', 'integer', 'min:0'],
            'author_name' => ['nullable', 'string', 'max:140'],
            'author_badge' => ['nullable', 'string', 'max:80'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'hidden'])],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function findContent(string $value): CommunityContentItem
    {
        return CommunityContentItem::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function uniqueSlug(string $type, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'content';
        $slug = $base;
        $suffix = 2;

        while (CommunityContentItem::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('type', $type)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
