<?php

namespace App\Plugins\Announcements\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityAnnouncementResource;
use App\Core\Models\CommunityAnnouncement;
use Illuminate\Http\Request;

class CommunityAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'pinned' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = CommunityAnnouncement::query()
            ->with('author')
            ->where('status', 'published');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['category'])) {
            $query->where('category_slug', $validated['category']);
        }

        if (array_key_exists('pinned', $validated)) {
            $query->where('is_pinned', (bool) $validated['pinned']);
        }

        $limit = (int) ($validated['limit'] ?? 25);

        return CommunityAnnouncementResource::collection(
            $query
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get()
        )->additional([
            'meta' => [
                'stats' => $this->stats(),
                'categories' => $this->categories(),
            ],
        ]);
    }

    public function show(string $announcement)
    {
        $announcementModel = $this->findPublishedAnnouncement($announcement)->load('author');
        $announcementModel->increment('views_count');
        $announcementModel->refresh()->load('author');

        return new CommunityAnnouncementResource($announcementModel);
    }

    private function findPublishedAnnouncement(string $value): CommunityAnnouncement
    {
        return CommunityAnnouncement::query()
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function stats(): array
    {
        $base = CommunityAnnouncement::query()->where('status', 'published');

        return [
            'total' => (clone $base)->count(),
            'pinned' => (clone $base)->where('is_pinned', true)->count(),
            'this_month' => (clone $base)
                ->whereBetween('published_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'total_views' => (int) (clone $base)->sum('views_count'),
            'total_comments' => (int) (clone $base)->sum('comments_count'),
        ];
    }

    private function categories(): array
    {
        return CommunityAnnouncement::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get(['category', 'category_slug', 'tone'])
            ->groupBy('category_slug')
            ->map(fn ($group) => [
                'name' => $group->first()->category,
                'slug' => $group->first()->category_slug,
                'tone' => $group->first()->tone,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
