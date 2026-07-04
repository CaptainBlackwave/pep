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

        // Community content notifications (announcements, lab results, guides, vendor reviews)
        $communityQuery = CommunityNotification::query()
            ->with('author')
            ->where('status', 'published');

        if ($user) {
            $communityQuery->with(['reads' => fn ($q) => $q->where('user_id', $user->id)]);
        }

        if ($categoryFilter && !in_array($categoryFilter, ['replies'])) {
            $communityQuery->where('category_slug', $categoryFilter);
        }

        if ($statusFilter === 'unread' && $user && !in_array($categoryFilter, ['replies'])) {
            $communityQuery->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id));
        }

        $communityNotifications = $communityQuery->get()->map(fn ($item) => [
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
            'unread' => $user ? !$item->reads?->contains('user_id', $user->id) : false,
            'href' => $item->href(),
        ])->toArray();

        // Discussion reply notifications (from notifications table, type != message)
        $replyNotifications = [];
        if ($user && (!$categoryFilter || in_array($categoryFilter, ['replies', 'all', 'unread']))) {
            $replyQuery = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', '!=', 'message');

            if ($statusFilter === 'unread') {
                $replyQuery->unread();
            }

            $replyNotifications = $replyQuery->latest()->limit(50)->get()->map(fn ($item) => [
                '_source' => 'reply',
                'id' => $item->id,
                'title' => $item->title,
                'slug' => (string) $item->id,
                'body' => $item->message,
                'icon' => $item->icon ?? 'bell',
                'category' => 'Replies',
                'category_slug' => 'replies',
                'published_at' => $item->created_at,
                'created_at' => $item->created_at,
                'is_pinned' => false,
                'unread' => $item->isUnread(),
                'href' => $item->link ?? '/',
            ])->toArray();
        }

        // Merge and sort
        $allNotifications = collect(array_merge($communityNotifications, $replyNotifications))
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
                'unread' => $user ? !$communityItem->reads?->contains('user_id', $user->id) : false,
                'href' => $communityItem->href(),
            ])]);
        }

        abort(404, 'Notification not found.');
    }

    public function markAsRead(Request $request, string $notification)
    {
        $user = $request->user();

        $parts = explode('_', $notification, 2);
        $source = $parts[0] ?? 'community';
        $realId = $parts[1] ?? $notification;

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
                return response()->json(['success' => true]);
            }
        }

        // Try reply notification
        if (ctype_digit($realId)) {
            $replyItem = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', '!=', 'message')
                ->whereKey((int) $realId)
                ->first();

            if ($replyItem) {
                $replyItem->markAsRead();
                return response()->json(['success' => true]);
            }
        }

        abort(404, 'Notification not found.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $unreadCommunity = CommunityNotification::query()
            ->where('status', 'published')
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->get(['id']);

        foreach ($unreadCommunity as $notification) {
            CommunityNotificationRead::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'read_at' => now(),
            ]);
        }

        Notification::where('user_id', $user->id)
            ->where('type', '!=', 'message')
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'read_count' => $unreadCommunity->count(),
        ]);
    }

    private function formatItem(array $item): array
    {
        $source = $item['_source'] ?? 'community';
        $body = $item['body'] ?? '';
        $time = $source === 'reply'
            ? ($item['created_at'] ?? null)
            : ($item['published_at'] ?? $item['created_at'] ?? null);

        return [
            'id' => "{$source}_{$item['id']}",
            'source' => $source,
            'title' => $item['title'] ?? '',
            'text' => $body,
            'body' => $body,
            'icon' => $item['icon'] ?? ($source === 'reply' ? 'bell' : 'megaphone'),
            'category' => $item['category'] ?? 'Update',
            'category_slug' => $item['category_slug'] ?? 'update',
            'unread' => $item['unread'] ?? false,
            'href' => $item['href'] ?? '/',
            'detail_href' => $source === 'community' ? "/notifications/{$item['slug']}" : ($item['href'] ?? '/'),
            'slug' => "{$source}_{$item['id']}",
            'time' => $time ? \Carbon\Carbon::parse($time)->diffForHumans() : '',
            'date' => $time ? \Carbon\Carbon::parse($time)->toIso8601String() : '',
            'tone' => 'info',
            'views' => 0,
        ];
    }

    private function stats(?int $userId): array
    {
        $base = CommunityNotification::query()->where('status', 'published');
        $communityUnread = $userId
            ? (clone $base)->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))->count()
            : 0;

        $replyUnread = $userId
            ? Notification::where('user_id', $userId)->where('type', '!=', 'message')->unread()->count()
            : 0;

        $replyTotal = $userId
            ? Notification::where('user_id', $userId)->where('type', '!=', 'message')->count()
            : 0;

        return [
            'total' => (clone $base)->count() + $replyTotal,
            'unread' => $communityUnread + $replyUnread,
            'announcements' => (clone $base)->where('category_slug', 'announcements')->count(),
            'lab_results' => (clone $base)->where('category_slug', 'lab-results')->count(),
            'discussions' => (clone $base)->where('category_slug', 'discussions')->count(),
            'vendors' => (clone $base)->where('category_slug', 'vendor-reviews')->count(),
            'replies_unread' => $replyUnread,
        ];
    }

    private function categories(?int $userId): array
    {
        $cats = CommunityNotification::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get(['category', 'category_slug', 'icon'])
            ->groupBy('category_slug')
            ->map(function ($group) use ($userId) {
                $first = $group->first();
                $base = CommunityNotification::query()
                    ->where('status', 'published')
                    ->where('category_slug', $first->category_slug);
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
            $replyUnread = Notification::where('user_id', $userId)->where('type', '!=', 'message')->unread()->count();
            if ($replyUnread > 0) {
                $cats[] = ['name' => 'Replies', 'slug' => 'replies', 'icon' => 'message', 'count' => $replyUnread, 'unread' => $replyUnread, 'latest' => now()];
            }
        }

        return $cats;
    }
}
