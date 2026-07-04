<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityAnnouncementResource;
use App\Core\Models\CommunityAnnouncement;
use App\Core\Services\CommunityNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityAnnouncementAdminController extends Controller
{
    public function __construct(private CommunityNotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'draft', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityAnnouncement::query()->with('author');

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

        return CommunityAnnouncementResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityAnnouncement::count(),
                    'published' => CommunityAnnouncement::where('status', 'published')->count(),
                    'draft' => CommunityAnnouncement::where('status', 'draft')->count(),
                    'hidden' => CommunityAnnouncement::where('status', 'hidden')->count(),
                    'pinned' => CommunityAnnouncement::where('is_pinned', true)->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAnnouncement($request);
        $user = $request->user();
        $status = $validated['status'] ?? 'draft';

        $announcement = CommunityAnnouncement::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['title']),
            'category_slug' => $validated['category_slug'] ?? Str::slug($validated['category'] ?? 'General'),
            'user_id' => $user?->id,
            'author_name' => $validated['author_name'] ?? null,
            'status' => $status,
            'published_at' => $validated['published_at'] ?? ($status === 'published' ? now() : null),
        ]);

        $this->notifications->syncAnnouncement($announcement);

        return (new CommunityAnnouncementResource($announcement->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $announcement)
    {
        $announcementModel = $this->findAnnouncement($announcement);
        $validated = $this->validateAnnouncement($request, true);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $announcementModel->id);
        }

        if (isset($validated['category']) && empty($validated['category_slug'])) {
            $validated['category_slug'] = Str::slug($validated['category']);
        }

        if (($validated['status'] ?? null) === 'published' && !$announcementModel->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $announcementModel->fill($validated)->save();

        $this->notifications->syncAnnouncement($announcementModel);

        return new CommunityAnnouncementResource($announcementModel->load('author'));
    }

    public function destroy(string $announcement)
    {
        $announcementModel = $this->findAnnouncement($announcement);
        $announcementModel->forceFill(['status' => 'hidden'])->save();
        $this->notifications->syncAnnouncement($announcementModel);

        return response()->json([
            'success' => true,
            'message' => 'Announcement hidden.',
        ]);
    }

    private function validateAnnouncement(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200'],
            'category' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:80'],
            'category_slug' => ['nullable', 'string', 'max:100'],
            'icon' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:40'],
            'tone' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:40'],
            'excerpt' => ['nullable', 'string', 'max:320'],
            'body' => [$required, 'string', 'max:20000'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'comments_count' => ['nullable', 'integer', 'min:0'],
            'views_count' => ['nullable', 'integer', 'min:0'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'hidden'])],
            'published_at' => ['nullable', 'date'],
            'author_name' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function findAnnouncement(string $value): CommunityAnnouncement
    {
        return CommunityAnnouncement::query()
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
        $base = Str::slug($value) ?: 'announcement';
        $slug = $base;
        $suffix = 2;

        while (CommunityAnnouncement::query()
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
