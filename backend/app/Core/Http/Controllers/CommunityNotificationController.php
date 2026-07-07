<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityNotificationResource;
use App\Core\Models\CommunityNotification;
use App\Core\Models\CommunityNotificationRead;
use App\Core\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CommunityNotificationController extends Controller
{
    private const PERSONAL_CATEGORIES = ['replies', 'mentions', 'messages', 'system'];

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

        $isPersonalCategory = $categoryFilter && in_array($categoryFilter, self::PERSONAL_CATEGORIES, true);

        $notifications = collect();

        if (!$categoryFilter || !$isPersonalCategory) {
            $communityQuery = $this->publishedCommunityQuery($user?->id);

            if ($categoryFilter) {
                $communityQuery->where('category_slug', $categoryFilter);
            }

            if ($statusFilter === 'unread') {
                $this->onlyUnreadCommunity($communityQuery, $user?->id);
            }

            $notifications = $notifications->merge(
                $communityQuery->get()->map(fn (CommunityNotification $item) => $this->communityNotificationPayload($request, $item))
            );
        }

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

        $notifications = $notifications
            ->merge($personalNotifications)
            ->sort(fn (array $a, array $b) => $this->sortNotifications($a, $b))
            ->values();

        $currentPage = (int) ($request->get('page', 1));
        $total = $notifications->count();
        $items = $notifications->forPage($currentPage, $limit);

        return response()->json([
            'data' => $items->values(),
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

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            return response()->json(['data' => $this->personalNotificationPayload($personalItem)]);
        }

        $communityItem = $this->findCommunityNotification($user?->id, $notification);
        if ($communityItem) {
            $communityItem->increment('views_count');
            $communityItem->refresh();
            $this->loadCommunityReadState($communityItem, $user?->id);

            return response()->json(['data' => $this->communityNotificationPayload($request, $communityItem)]);
        }

        abort(404, 'Notification not found.');
    }

    public function markAsRead(Request $request, string $notification)
    {
        $user = $request->user();

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            $personalItem->markAsRead();
            $personalItem->refresh();
            return response()->json(['data' => $this->personalNotificationPayload($personalItem)]);
        }

        $communityItem = $this->findCommunityNotification($user?->id, $notification);
        if ($communityItem) {
            CommunityNotificationRead::updateOrCreate(
                [
                    'notification_id' => $communityItem->id,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => now(),
                    'dismissed_at' => null,
                ]
            );

            $this->loadCommunityReadState($communityItem, $user->id);

            return response()->json(['data' => $this->communityNotificationPayload($request, $communityItem)]);
        }

        abort(404, 'Notification not found.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $communityIds = $this->publishedCommunityQuery($user->id)->pluck('id');
        $readCommunityIds = CommunityNotificationRead::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_id', $communityIds)
            ->whereNotNull('read_at')
            ->whereNull('dismissed_at')
            ->pluck('notification_id');

        $unreadCommunityIds = $communityIds->diff($readCommunityIds)->values();
        foreach ($unreadCommunityIds as $notificationId) {
            CommunityNotificationRead::updateOrCreate(
                [
                    'notification_id' => $notificationId,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => now(),
                    'dismissed_at' => null,
                ]
            );
        }

        $personalReadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'read_count' => $unreadCommunityIds->count() + $personalReadCount,
        ]);
    }

    public function delete(Request $request, string $notification)
    {
        $user = $request->user();

        $personalItem = $this->findPersonalNotification($user, $notification);
        if ($personalItem) {
            $personalItem->delete();
            return response()->json(['success' => true, 'message' => 'Notification deleted.']);
        }

        $communityItem = $this->findCommunityNotification($user?->id, $notification);
        if ($communityItem) {
            CommunityNotificationRead::updateOrCreate(
                [
                    'notification_id' => $communityItem->id,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => now(),
                    'dismissed_at' => now(),
                ]
            );

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
            ->whereHas('notification', fn ($query) => $query->where('status', 'published'))
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

    private function stats(?int $userId): array
    {
        $communityQuery = $this->publishedCommunityQuery($userId);
        $communityTotal = (clone $communityQuery)->count();
        $communityRead = $userId
            ? CommunityNotificationRead::query()
                ->where('user_id', $userId)
                ->whereNotNull('read_at')
                ->whereNull('dismissed_at')
                ->whereHas('notification', fn ($query) => $query->where('status', 'published'))
                ->count()
            : 0;
        $communityUnread = $userId ? max(0, $communityTotal - $communityRead) : $communityTotal;

        $personalUnread = $userId
            ? Notification::where('user_id', $userId)->unread()->count()
            : 0;

        $personalTotal = $userId
            ? Notification::where('user_id', $userId)->count()
            : 0;

        $total = $communityTotal + $personalTotal;
        $unread = $communityUnread + $personalUnread;

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => max(0, $total - $unread),
            'announcements' => (clone $communityQuery)->where('category_slug', 'announcements')->count(),
            'lab_results' => (clone $communityQuery)->where('category_slug', 'lab-results')->count(),
            'discussions' => (clone $communityQuery)->whereIn('category_slug', ['discussions', 'discussion-replies'])->count(),
            'vendors' => (clone $communityQuery)->whereIn('category_slug', ['vendors', 'vendor-reviews'])->count(),
            'replies_unread' => $userId ? $this->personalCategoryQuery($userId, 'replies')->unread()->count() : 0,
            'mentions_unread' => $userId ? $this->personalCategoryQuery($userId, 'mentions')->unread()->count() : 0,
            'messages_unread' => $userId ? $this->personalCategoryQuery($userId, 'messages')->unread()->count() : 0,
            'system_unread' => $userId ? $this->personalCategoryQuery($userId, 'system')->unread()->count() : 0,
        ];
    }

    private function categories(?int $userId): array
    {
        $communityCategories = $this->publishedCommunityQuery($userId)
            ->get()
            ->groupBy('category_slug')
            ->map(function (Collection $items, string $slug) {
                /** @var CommunityNotification $first */
                $first = $items->sortByDesc(fn (CommunityNotification $item) => $item->published_at ?: $item->created_at)->first();
                $latest = $items
                    ->map(fn (CommunityNotification $item) => $item->published_at ?: $item->created_at)
                    ->filter()
                    ->sortDesc()
                    ->first();

                return [
                    'name' => $first?->category ?? str($slug)->headline()->toString(),
                    'slug' => $slug,
                    'icon' => $first?->icon ?? 'bell',
                    'count' => $items->count(),
                    'unread' => $items->filter(fn (CommunityNotification $item) => $this->communityNotificationIsUnread($item))->count(),
                    'latest' => $latest?->toIso8601String(),
                ];
            })
            ->sortByDesc('latest')
            ->values()
            ->all();

        $cats = $communityCategories;

        if ($userId) {
            foreach (self::PERSONAL_CATEGORIES as $slug) {
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
        $slug = "personal_{$item->id}";

        return [
            '_source' => 'personal',
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $slug,
            'body' => $item->message,
            'excerpt' => $item->message,
            'icon' => $meta['icon'],
            'tone' => $meta['tone'],
            'category' => $meta['category'],
            'category_slug' => $meta['slug'],
            'published_at' => $item->created_at?->toIso8601String(),
            'published_label' => $item->created_at?->format('M j, Y · h:i A'),
            'time_ago' => $item->created_at?->diffForHumans(),
            'created_at' => $item->created_at?->toIso8601String(),
            'read_at' => $item->read_at?->toIso8601String(),
            'is_pinned' => false,
            'unread' => $item->isUnread(),
            'source_url' => $item->link ?? '/',
            'detail_href' => "/notifications/{$slug}",
            'href' => $item->link ?? '/',
            'author' => [
                'name' => '',
                'initial' => '',
            ],
            'views' => 0,
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

        if (str_starts_with($value, 'personal_')) {
            $realId = substr($value, strlen('personal_'));
        } elseif (ctype_digit($value)) {
            $realId = $value;
        } else {
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

    private function publishedCommunityQuery(?int $userId)
    {
        return CommunityNotification::query()
            ->where('status', 'published')
            ->with('author')
            ->with(['reads' => function ($query) use ($userId) {
                $userId
                    ? $query->where('user_id', $userId)
                    : $query->whereRaw('1 = 0');
            }])
            ->when($userId, function ($query) use ($userId) {
                $query->whereDoesntHave('reads', function ($readQuery) use ($userId) {
                    $readQuery
                        ->where('user_id', $userId)
                        ->whereNotNull('dismissed_at');
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    private function onlyUnreadCommunity($query, ?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $query->whereDoesntHave('reads', function ($readQuery) use ($userId) {
            $readQuery
                ->where('user_id', $userId)
                ->whereNotNull('read_at');
        });
    }

    private function communityNotificationPayload(Request $request, CommunityNotification $notification): array
    {
        return (new CommunityNotificationResource($notification))->toArray($request);
    }

    private function findCommunityNotification(?int $userId, string $value): ?CommunityNotification
    {
        if (str_starts_with($value, 'community_')) {
            $value = substr($value, strlen('community_'));
        }

        if ($value === '' || str_starts_with($value, 'personal_')) {
            return null;
        }

        return $this->publishedCommunityQuery($userId)
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->first();
    }

    private function loadCommunityReadState(CommunityNotification $notification, ?int $userId): void
    {
        $notification->load([
            'author',
            'reads' => function ($query) use ($userId) {
                $userId
                    ? $query->where('user_id', $userId)
                    : $query->whereRaw('1 = 0');
            },
        ]);
    }

    private function communityNotificationIsUnread(CommunityNotification $notification): bool
    {
        if (!$notification->relationLoaded('reads')) {
            return true;
        }

        $read = $notification->reads->first();

        return !$read || $read->read_at === null;
    }

    private function sortNotifications(array $a, array $b): int
    {
        $pinned = ((bool) ($b['is_pinned'] ?? false)) <=> ((bool) ($a['is_pinned'] ?? false));
        if ($pinned !== 0) {
            return $pinned;
        }

        $time = $this->notificationTimestamp($b) <=> $this->notificationTimestamp($a);
        if ($time !== 0) {
            return $time;
        }

        return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
    }

    private function notificationTimestamp(array $item): int
    {
        $value = $item['published_at'] ?? $item['created_at'] ?? null;

        if (!$value) {
            return 0;
        }

        return strtotime((string) $value) ?: 0;
    }
}
