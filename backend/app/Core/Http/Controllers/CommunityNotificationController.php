<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CommunityNotification;
use App\Core\Models\CommunityNotificationRead;
use App\Core\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityNotificationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'unread'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user('sanctum') ?: $request->user();
        $limit = (int) ($validated['limit'] ?? 50);
        $categoryFilter = $validated['category'] ?? null;
        $statusFilter = $validated['status'] ?? null;

        $personalCategories = ['replies', 'mentions', 'messages', 'system'];
        $isPersonalCategory = $categoryFilter && in_array($categoryFilter, $personalCategories, true);

        // Community content notifications (announcements, lab results, guides, vendor reviews)
        $communityQuery = CommunityNotification::query()
            ->with('author')
            ->where('status', 'published');

        if ($user) {
            $communityQuery->with(['reads' => fn ($q) => $q->where('user_id', $user->id)]);
            $communityQuery->whereDoesntHave('reads', fn ($q) => $q
                ->where('user_id', $user->id)
                ->whereNotNull('dismissed_at'));
        }

        if ($categoryFilter && !$isPersonalCategory) {
            $communityQuery->where('category_slug', $categoryFilter);
        }

        if ($statusFilter === 'unread' && $user && !$isPersonalCategory) {
            $communityQuery->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id));
        }

        $communityNotifications = $isPersonalCategory ? [] : $communityQuery->get()->map(fn ($item) => [
            '_source' => 'community',
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'body' => $item->body,
            'icon' => $item->icon,
            'category' => $item->category,
            'category_slug' => $item->category_slug,
            'published_at' => $item->published_at,
            'created_at' => $item->created_at,
            'is_pinned' => $item->is_pinned,
            'views_count' => $item->views_count,
            'unread' => $user ? !$item->reads?->contains('user_id', $user->id) : false,
            'href' => $item->href(),
        ])->toArray();

        $personalNotifications = [];
        if ($user && (!$categoryFilter || $isPersonalCategory)) {
            $personalQuery = Notification::query()
                ->where('user_id', $user->id);

            if ($categoryFilter) {
                $this->applyPersonalCategoryFilter($personalQuery, $categoryFilter);
            }
            if ($statusFilter === 'unread') {
                $personalQuery->unread();
            }

            $personalNotifications = $personalQuery->latest()->limit(50)->get()
                ->map(fn (Notification $item) => $this->personalNotificationPayload($item))
                ->toArray();
        }

        // Merge and sort
        $allNotifications = collect(array_merge($communityNotifications, $personalNotifications))
            ->sortByDesc(fn ($n) => $n['published_at'] ?? $n['created_at'])
            ->values();

        $currentPage = (int) ($request->get('page', 1));
        $total = $allNotifications->count();
        $items = $allNotifications->forPage($currentPage, $limit);

        return response()->json([
            'data' => $items->map(fn ($item) => $this->formatItem($item))->values(),
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => max(1, (int) ceil($total / $limit)),
                'per_page' => $limit,
                'total' => $total,
                'stats' => $this->stats($user?->id),
                'categories' => $this->categories($user?->id),
            ],
        ]);
    }

    public function show(Request $request, string $notification)
    {
        $user = $request->user('sanctum') ?: $request->user();

        $communityItem = CommunityNotification::query()
            ->where('status', 'published')
            ->where(function ($q) use ($notification) {
                $q->where('slug', $notification);
                if (ctype_digit($notification)) $q->orWhere('id', (int) $notification);
            })
            ->first();

        if ($communityItem) {
            $communityItem->load('author');
            $communityItem->increment('views_count');
            if ($user) {
                $communityItem->load(['reads' => fn ($q) => $q->where('user_id', $user->id)]);
            }
            return response()->json(['data' => $this->formatItem([
                '_source' => 'community',
                'id' => $communityItem->id,
                'title' => $communityItem->title,
                'slug' => $communityItem->slug,
                'body' => $communityItem->body,
                'icon' => $communityItem->icon,
                'category' => $communityItem->category,
                'category_slug' => $communityItem->category_slug,
                'published_at' => $communityItem->published_at,
                'created_at' => $communityItem->created_at,
                'is_pinned' => $communityItem->is_pinned,
                'views_count' => $communityItem->views_count,
                'unread' => $user ? !$communityItem->reads?->contains('user_id', $user->id) : false,
                'href' => $communityItem->href(),
            ])]);
        }

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            return response()->json(['data' => $this->formatItem($this->personalNotificationPayload($personalItem))]);
        }

        abort(404, 'Notification not found.');
    }

    public function markAsRead(Request $request, string $notification)
    {
        $user = $request->user();

        [$source, $realId] = $this->parseNotificationIdentifier($notification);

        // Try community notification
        if ($source === 'community') {
            $communityItem = CommunityNotification::query()
                ->where('status', 'published')
                ->where(function ($q) use ($realId) {
                    $q->where('slug', $realId);
                    if (ctype_digit($realId)) $q->orWhere('id', (int) $realId);
                })
                ->first();

            if ($communityItem) {
                CommunityNotificationRead::updateOrCreate(
                    ['notification_id' => $communityItem->id, 'user_id' => $user->id],
                    ['read_at' => now()]
                );
                $communityItem->setRelation('reads', collect([
                    new CommunityNotificationRead(['user_id' => $user->id, 'read_at' => now()]),
                ]));
                return response()->json(['data' => $this->formatItem([
                    '_source' => 'community',
                    'id' => $communityItem->id,
                    'title' => $communityItem->title,
                    'slug' => $communityItem->slug,
                    'body' => $communityItem->body,
                    'icon' => $communityItem->icon,
                    'category' => $communityItem->category,
                    'category_slug' => $communityItem->category_slug,
                    'published_at' => $communityItem->published_at,
                    'created_at' => $communityItem->created_at,
                    'is_pinned' => $communityItem->is_pinned,
                    'views_count' => $communityItem->views_count,
                    'unread' => false,
                    'href' => $communityItem->href(),
                ])]);
            }
        }

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            $personalItem->markAsRead();
            $personalItem->refresh();
            return response()->json(['data' => $this->formatItem($this->personalNotificationPayload($personalItem))]);
        }

        abort(404, 'Notification not found.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $unreadCommunity = CommunityNotification::query()
            ->where('status', 'published')
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->whereDoesntHave('reads', fn ($q) => $q
                ->where('user_id', $user->id)
                ->whereNotNull('dismissed_at'))
            ->get(['id']);

        foreach ($unreadCommunity as $notification) {
            CommunityNotificationRead::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'read_at' => now(),
            ]);
        }

        $personalReadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'read_count' => $unreadCommunity->count() + $personalReadCount,
        ]);
    }

    public function delete(Request $request, string $notification)
    {
        $user = $request->user();

        [$source, $realId] = $this->parseNotificationIdentifier($notification);

        if ($source === 'community') {
            $communityItem = CommunityNotification::query()
                ->where('status', 'published')
                ->where(function ($q) use ($realId) {
                    $q->where('slug', $realId);
                    if (ctype_digit($realId)) $q->orWhere('id', (int) $realId);
                })
                ->first();

            if ($communityItem) {
                CommunityNotificationRead::updateOrCreate(
                    ['notification_id' => $communityItem->id, 'user_id' => $user->id],
                    ['read_at' => now(), 'dismissed_at' => now()]
                );

                return response()->json(['success' => true, 'message' => 'Notification dismissed.']);
            }
        }

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            $personalItem->delete();

            return response()->json(['success' => true, 'message' => 'Notification deleted.']);
        }

        abort(404, 'Notification not found.');
    }

    public function deleteRead(Request $request)
    {
        $user = $request->user();

        $communityCount = CommunityNotificationRead::query()
            ->where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->whereNull('dismissed_at')
            ->update(['dismissed_at' => now()]);

        $personalCount = Notification::query()
            ->where('user_id', $user->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'count' => $communityCount + $personalCount,
            'message' => 'Read notifications cleared.',
        ]);
    }

    private function formatItem(array $item): array
    {
        $source = $item['_source'] ?? 'community';
        $body = $item['body'] ?? '';
        $time = $source === 'personal'
            ? ($item['created_at'] ?? null)
            : ($item['published_at'] ?? $item['created_at'] ?? null);

        return [
            'id' => "{$source}_{$item['id']}",
            'source' => $source,
            'title' => $item['title'] ?? '',
            'text' => $body,
            'body' => $body,
            'icon' => $item['icon'] ?? ($source === 'personal' ? 'bell' : 'megaphone'),
            'category' => $item['category'] ?? 'Update',
            'category_slug' => $item['category_slug'] ?? 'update',
            'unread' => $item['unread'] ?? false,
            'href' => $item['href'] ?? '/',
            'detail_href' => $source === 'community' ? "/notifications/{$item['slug']}" : ($item['href'] ?? "/notifications/personal_{$item['id']}"),
            'slug' => $source === 'community' ? (string) ($item['slug'] ?? $item['id']) : "personal_{$item['id']}",
            'time' => $time ? \Carbon\Carbon::parse($time)->diffForHumans() : '',
            'date' => $time ? \Carbon\Carbon::parse($time)->toIso8601String() : '',
            'tone' => $item['tone'] ?? 'info',
            'views' => (int) ($item['views_count'] ?? 0),
        ];
    }

    private function stats(?int $userId): array
    {
        $base = CommunityNotification::query()->where('status', 'published');
        if ($userId) {
            $base->whereDoesntHave('reads', fn ($q) => $q
                ->where('user_id', $userId)
                ->whereNotNull('dismissed_at'));
        }

        $communityUnread = $userId
            ? (clone $base)->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))->count()
            : 0;

        $personalUnread = $userId
            ? Notification::where('user_id', $userId)->unread()->count()
            : 0;

        $personalTotal = $userId
            ? Notification::where('user_id', $userId)->count()
            : 0;

        return [
            'total' => (clone $base)->count() + $personalTotal,
            'unread' => $communityUnread + $personalUnread,
            'announcements' => (clone $base)->where('category_slug', 'announcements')->count(),
            'lab_results' => (clone $base)->where('category_slug', 'lab-results')->count(),
            'discussions' => (clone $base)->where('category_slug', 'discussions')->count(),
            'vendors' => (clone $base)->where('category_slug', 'vendor-reviews')->count(),
            'replies_unread' => $userId ? $this->personalCategoryQuery($userId, 'replies')->unread()->count() : 0,
            'mentions_unread' => $userId ? $this->personalCategoryQuery($userId, 'mentions')->unread()->count() : 0,
            'messages_unread' => $userId ? $this->personalCategoryQuery($userId, 'messages')->unread()->count() : 0,
            'system_unread' => $userId ? $this->personalCategoryQuery($userId, 'system')->unread()->count() : 0,
        ];
    }

    private function categories(?int $userId): array
    {
        $cats = CommunityNotification::query()
            ->where('status', 'published')
            ->when($userId, fn ($query) => $query->whereDoesntHave('reads', fn ($q) => $q
                ->where('user_id', $userId)
                ->whereNotNull('dismissed_at')))
            ->orderByDesc('published_at')
            ->get(['category', 'category_slug', 'icon'])
            ->groupBy('category_slug')
            ->map(function ($group) use ($userId) {
                $first = $group->first();
                $base = CommunityNotification::query()
                    ->where('status', 'published')
                    ->where('category_slug', $first->category_slug);
                if ($userId) {
                    $base->whereDoesntHave('reads', fn ($q) => $q
                        ->where('user_id', $userId)
                        ->whereNotNull('dismissed_at'));
                }
                return [
                    'name' => $first->category,
                    'slug' => $first->category_slug,
                    'icon' => $first->icon,
                    'count' => $group->count(),
                    'unread' => $userId
                        ? (clone $base)->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))->count()
                        : 0,
                    'latest' => (clone $base)->max('published_at'),
                ];
            })->values()->all();

        if ($userId) {
            foreach (['replies', 'mentions', 'messages', 'system'] as $slug) {
                $query = $this->personalCategoryQuery($userId, $slug);
                $count = (clone $query)->count();
                if ($count === 0) {
                    continue;
                }
                $meta = $this->personalCategoryMeta($slug);
                $cats[] = [
                    'name' => $meta['category'],
                    'slug' => $slug,
                    'icon' => $meta['icon'],
                    'count' => $count,
                    'unread' => (clone $query)->unread()->count(),
                    'latest' => (clone $query)->max('created_at'),
                ];
            }
        }

        return $cats;
    }

    private function applyPersonalCategoryFilter($query, string $category): void
    {
        match ($category) {
            'replies' => $query->where('type', 'discussion_reply'),
            'mentions' => $query->where('type', 'discussion_mention'),
            'messages' => $query->where('type', 'message'),
            'system' => $query->whereNotIn('type', ['discussion_reply', 'discussion_mention', 'message']),
            default => null,
        };
    }

    private function personalCategoryQuery(int $userId, string $category)
    {
        $query = Notification::query()->where('user_id', $userId);
        $this->applyPersonalCategoryFilter($query, $category);

        return $query;
    }

    private function personalNotificationPayload(Notification $item): array
    {
        $meta = $this->personalCategoryMetaForType($item->type);

        return [
            '_source' => 'personal',
            'id' => $item->id,
            'title' => $item->title,
            'slug' => (string) $item->id,
            'body' => $item->message,
            'icon' => $meta['icon'],
            'tone' => $meta['tone'],
            'category' => $meta['category'],
            'category_slug' => $meta['slug'],
            'published_at' => $item->created_at,
            'created_at' => $item->created_at,
            'is_pinned' => false,
            'unread' => $item->isUnread(),
            'href' => $item->link ?? '/',
        ];
    }

    private function personalCategoryMetaForType(string $type): array
    {
        return match ($type) {
            'discussion_reply' => $this->personalCategoryMeta('replies'),
            'discussion_mention' => $this->personalCategoryMeta('mentions'),
            'message' => $this->personalCategoryMeta('messages'),
            default => $this->personalCategoryMeta('system'),
        };
    }

    private function personalCategoryMeta(string $slug): array
    {
        return match ($slug) {
            'replies' => ['category' => 'Replies', 'slug' => 'replies', 'icon' => 'message', 'tone' => 'purple'],
            'mentions' => ['category' => 'Mentions', 'slug' => 'mentions', 'icon' => 'users', 'tone' => 'blue'],
            'messages' => ['category' => 'Messages', 'slug' => 'messages', 'icon' => 'mail', 'tone' => 'green'],
            default => ['category' => 'System', 'slug' => 'system', 'icon' => 'bell', 'tone' => 'info'],
        };
    }

    private function findPersonalNotification($user, string $value): ?Notification
    {
        if (!$user) {
            return null;
        }

        [$source, $realId] = $this->parseNotificationIdentifier($value);
        if ($source !== 'personal') {
            return null;
        }

        if (!ctype_digit($realId)) {
            return null;
        }

        return Notification::query()
            ->where('user_id', $user->id)
            ->whereKey((int) $realId)
            ->first();
    }

    private function parseNotificationIdentifier(string $value): array
    {
        $parts = explode('_', $value, 2);
        $prefix = $parts[0] ?? '';

        if (in_array($prefix, ['community', 'personal'], true)) {
            return [$prefix, $parts[1] ?? ''];
        }

        return ['community', $value];
    }
}
