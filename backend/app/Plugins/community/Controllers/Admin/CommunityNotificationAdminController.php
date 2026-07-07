<?php

namespace App\Plugins\Community\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityNotificationResource;
use App\Core\Models\CommunityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityNotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'draft', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityNotification::query()->with('author');

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

        return CommunityNotificationResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityNotification::count(),
                    'published' => CommunityNotification::where('status', 'published')->count(),
                    'draft' => CommunityNotification::where('status', 'draft')->count(),
                    'hidden' => CommunityNotification::where('status', 'hidden')->count(),
                    'pinned' => CommunityNotification::where('is_pinned', true)->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateNotification($request);
        $user = $request->user();
        $status = $validated['status'] ?? 'draft';
        $category = $validated['category'];

        $notification = CommunityNotification::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['title']),
            'category_slug' => $validated['category_slug'] ?? Str::slug($category),
            'user_id' => $user?->id,
            'author_name' => $validated['author_name'] ?? null,
            'status' => $status,
            'published_at' => $validated['published_at'] ?? ($status === 'published' ? now() : null),
        ]);

        return (new CommunityNotificationResource($notification->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $notification)
    {
        $notificationModel = $this->findNotification($notification);
        $validated = $this->validateNotification($request, true);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $notificationModel->id);
        }

        if (isset($validated['category']) && empty($validated['category_slug'])) {
            $validated['category_slug'] = Str::slug($validated['category']);
        }

        if (($validated['status'] ?? null) === 'published' && !$notificationModel->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $notificationModel->fill($validated)->save();

        return new CommunityNotificationResource($notificationModel->load('author'));
    }

    public function destroy(string $notification)
    {
        $notificationModel = $this->findNotification($notification);
        $notificationModel->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification hidden.',
        ]);
    }

    private function validateNotification(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:220'],
            'category' => [$required, 'string', 'max:80'],
            'category_slug' => ['nullable', 'string', 'max:100'],
            'icon' => [$partial ? 'sometimes' : 'required', 'string', 'max:40'],
            'tone' => [$partial ? 'sometimes' : 'required', 'string', 'max:40'],
            'excerpt' => ['nullable', 'string', 'max:420'],
            'body' => ['nullable', 'string', 'max:20000'],
            'source_type' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_url' => ['nullable', 'string', 'max:255'],
            'views_count' => ['nullable', 'integer', 'min:0'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'hidden'])],
            'published_at' => ['nullable', 'date'],
            'author_name' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function findNotification(string $value): CommunityNotification
    {
        return CommunityNotification::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'notification';
        $slug = $base;
        $suffix = 2;

        while (CommunityNotification::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
