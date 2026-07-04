<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityContentItemResource;
use App\Core\Models\CommunityContentItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityContentController extends Controller
{
    public function researchIndex(Request $request)
    {
        return $this->index($request, 'research');
    }

    public function researchShow(string $content)
    {
        return $this->show('research', $content);
    }

    public function guideIndex(Request $request)
    {
        return $this->index($request, 'guide');
    }

    public function guideShow(string $content)
    {
        return $this->show('guide', $content);
    }

    public function faqIndex(Request $request)
    {
        return $this->index($request, 'faq');
    }

    private function index(Request $request, string $type)
    {
        $sortOptions = $this->sortOptions();
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:120'],
            'compound' => ['nullable', 'string', 'max:160'],
            'sort' => ['nullable', Rule::in(array_column($sortOptions, 'value'))],
            'published_from' => ['nullable', 'date'],
            'published_to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $query = CommunityContentItem::query()
            ->with('author')
            ->where('type', $type)
            ->where('status', 'published');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['category'])) {
            $query->where('category_slug', $validated['category']);
        }

        if (!empty($validated['tag'])) {
            $query->where('tag', $validated['tag']);
        }

        if (!empty($validated['compound'])) {
            $query->where('metadata->compound', $validated['compound']);
        }

        if (!empty($validated['published_from'])) {
            $query->whereDate('published_at', '>=', $validated['published_from']);
        }

        if (!empty($validated['published_to'])) {
            $query->whereDate('published_at', '<=', $validated['published_to']);
        }

        $limit = (int) ($validated['limit'] ?? 30);
        $this->applySort($query, $validated['sort'] ?? 'latest');

        return CommunityContentItemResource::collection(
            $query->limit($limit)->get()
        )->additional([
            'meta' => [
                'categories' => $this->categories($type),
                'topics' => $this->topics($type),
                'stats' => $this->stats($type),
                'filters' => $this->filters($type, $sortOptions),
            ],
        ]);
    }

    private function show(string $type, string $value)
    {
        $item = CommunityContentItem::query()
            ->with('author')
            ->where('type', $type)
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();

        $item->increment('views_count');
        $item->refresh()->load('author');

        return new CommunityContentItemResource($item);
    }

    private function categories(string $type): array
    {
        return CommunityContentItem::query()
            ->where('type', $type)
            ->where('status', 'published')
            ->selectRaw('category, category_slug, count(*) as count')
            ->groupBy('category', 'category_slug')
            ->orderByDesc('count')
            ->get()
            ->map(fn (CommunityContentItem $item) => [
                'name' => $item->category,
                'slug' => $item->category_slug,
                'count' => (int) $item->count,
            ])
            ->values()
            ->all();
    }

    private function topics(string $type): array
    {
        return CommunityContentItem::query()
            ->where('type', $type)
            ->where('status', 'published')
            ->whereNotNull('tag')
            ->selectRaw('tag as name, count(*) as count')
            ->groupBy('tag')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'count' => (int) $item->count,
            ])
            ->values()
            ->all();
    }

    private function stats(string $type): array
    {
        $base = CommunityContentItem::query()->where('type', $type)->where('status', 'published');

        return [
            'total' => (clone $base)->count(),
            'views' => (int) (clone $base)->sum('views_count'),
            'downloads' => (int) (clone $base)->sum('downloads_count'),
            'comments' => (int) (clone $base)->sum('comments_count'),
        ];
    }

    private function filters(string $type, array $sortOptions): array
    {
        $base = CommunityContentItem::query()
            ->where('type', $type)
            ->where('status', 'published');
        $items = (clone $base)->get(['category', 'category_slug', 'tag', 'metadata', 'published_at']);
        $firstPublished = (clone $base)->min('published_at');
        $lastPublished = (clone $base)->max('published_at');

        return [
            'categories' => $this->categories($type),
            'tags' => $items
                ->pluck('tag')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'compounds' => $items
                ->map(fn (CommunityContentItem $item) => is_array($item->metadata) ? ($item->metadata['compound'] ?? null) : null)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'sorts' => $sortOptions,
            'date_bounds' => [
                'from' => $firstPublished ? substr((string) $firstPublished, 0, 10) : null,
                'to' => $lastPublished ? substr((string) $lastPublished, 0, 10) : null,
            ],
        ];
    }

    private function sortOptions(): array
    {
        return [
            ['value' => 'latest', 'label' => 'Latest Added'],
            ['value' => 'oldest', 'label' => 'Oldest First'],
            ['value' => 'popular', 'label' => 'Most Viewed'],
            ['value' => 'downloaded', 'label' => 'Most Downloaded'],
            ['value' => 'discussed', 'label' => 'Most Discussed'],
            ['value' => 'title', 'label' => 'Title A-Z'],
        ];
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('published_at')->orderBy('id'),
            'popular' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            'downloaded' => $query->orderByDesc('downloads_count')->orderByDesc('published_at'),
            'discussed' => $query->orderByDesc('comments_count')->orderByDesc('published_at'),
            'title' => $query->orderBy('title')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }
}
