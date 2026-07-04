<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityNotificationResource;
use App\Core\Models\CommunityNotification;
use App\Core\Models\CommunityNotificationRead;
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
        $query = CommunityNotification::query()
            ->with('author')
            ->where('status', 'published');

        if ($user) {
            $query->with(['reads' => fn ($reads) => $reads->where('user_id', $user->id)]);
        }

        if (!empty($validated['category'])) {
            $query->where('category_slug', $validated['category']);
        }

        if (($validated['status'] ?? null) === 'unread' && $user) {
            $query->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id));
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityNotificationResource::collection(
            $query
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get()
        )->additional([
            'meta' => [
                'stats' => $this->stats($user?->id),
                'categories' => $this->categories($user?->id),
            ],
        ]);
    }

    public function show(Request $request, string $notification)
    {
        $user = $request->user('sanctum') ?: $request->user();
        $notificationModel = $this->findPublishedNotification($notification)->load('author');

        if ($user) {
            $notificationModel->load(['reads' => fn ($reads) => $reads->where('user_id', $user->id)]);
        }

        $notificationModel->increment('views_count');
        $notificationModel->refresh()->load('author');

        if ($user) {
            $notificationModel->load(['reads' => fn ($reads) => $reads->where('user_id', $user->id)]);
        }

        return new CommunityNotificationResource($notificationModel);
    }

    public function markAsRead(Request $request, string $notification)
    {
        $notificationModel = $this->findPublishedNotification($notification);

        CommunityNotificationRead::updateOrCreate(
            [
                'notification_id' => $notificationModel->id,
                'user_id' => $request->user()->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        $notificationModel->load([
            'author',
            'reads' => fn ($reads) => $reads->where('user_id', $request->user()->id),
        ]);

        return new CommunityNotificationResource($notificationModel);
    }

    public function markAllAsRead(Request $request)
    {
        $notifications = CommunityNotification::query()
            ->where('status', 'published')
            ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $request->user()->id))
            ->get(['id']);

        foreach ($notifications as $notification) {
            CommunityNotificationRead::create([
                'notification_id' => $notification->id,
                'user_id' => $request->user()->id,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'read_count' => $notifications->count(),
            'message' => 'Notifications marked as read.',
        ]);
    }

    private function findPublishedNotification(string $value): CommunityNotification
    {
        return CommunityNotification::query()
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    /**
     * @return array<string, int>
     */
    private function stats(?int $userId): array
    {
        $base = CommunityNotification::query()->where('status', 'published');
        $unread = $userId
            ? (clone $base)->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $userId))->count()
            : 0;

        return [
            'total' => (clone $base)->count(),
            'unread' => $unread,
            'announcements' => (clone $base)->where('category_slug', 'announcements')->count(),
            'lab_results' => (clone $base)->where('category_slug', 'lab-results')->count(),
            'discussions' => (clone $base)->where('category_slug', 'discussions')->count(),
            'vendors' => (clone $base)->where('category_slug', 'vendor-reviews')->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categories(?int $userId): array
    {
        return CommunityNotification::query()
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
                        ? (clone $base)->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $userId))->count()
                        : 0,
                    'latest' => (clone $base)->max('published_at'),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
