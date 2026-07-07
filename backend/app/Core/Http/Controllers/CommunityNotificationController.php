<?php

namespace App\Core\Http\Controllers;

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

        $currentPage = (int) ($request->get('page', 1));
        $total = count($personalNotifications);
        $items = collect($personalNotifications)->forPage($currentPage, $limit);

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

        abort(404, 'Notification not found.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $personalReadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'read_count' => $personalReadCount,
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

        abort(404, 'Notification not found.');
    }

    public function deleteRead(Request $request)
    {
        $user = $request->user();

        $personalCount = Notification::query()
            ->where('user_id', $user->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'count' => $personalCount,
            'message' => 'Read notifications cleared.',
        ]);
    }

    private function stats(?int $userId): array
    {
        $personalUnread = $userId
            ? Notification::where('user_id', $userId)->unread()->count()
            : 0;

        $personalTotal = $userId
            ? Notification::where('user_id', $userId)->count()
            : 0;

        return [
            'total' => $personalTotal,
            'unread' => $personalUnread,
            'announcements' => 0,
            'lab_results' => 0,
            'discussions' => 0,
            'vendors' => 0,
            'replies_unread' => $userId ? $this->personalCategoryQuery($userId, 'replies')->unread()->count() : 0,
            'mentions_unread' => $userId ? $this->personalCategoryQuery($userId, 'mentions')->unread()->count() : 0,
            'messages_unread' => $userId ? $this->personalCategoryQuery($userId, 'messages')->unread()->count() : 0,
            'system_unread' => $userId ? $this->personalCategoryQuery($userId, 'system')->unread()->count() : 0,
        ];
    }

    private function categories(?int $userId): array
    {
        $cats = [];

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

        $parts = explode('_', $value, 2);
        $source = $parts[0] ?? '';
        $realId = $parts[1] ?? $value;

        if ($source !== 'personal' && $source !== 'community') {
            $realId = $value;
        }

        if (!ctype_digit($realId)) {
            return null;
        }

        return Notification::query()
            ->where('user_id', $user->id)
            ->whereKey((int) $realId)
            ->first();
    }
}
