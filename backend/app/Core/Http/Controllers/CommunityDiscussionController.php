<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityDiscussionReplyResource;
use App\Core\Http\Resources\CommunityDiscussionResource;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionCategory;
use App\Core\Models\CommunityDiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityDiscussionController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = CommunityDiscussion::query()
            ->with(['category', 'user'])
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
            $category = $validated['category'];
            $query->whereHas('category', fn ($inner) => $inner->where('slug', $category));
        }

        $limit = (int) ($validated['limit'] ?? 25);
        $discussions = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return CommunityDiscussionResource::collection($discussions)
            ->additional([
                'meta' => [
                    'categories' => $this->categorySummary(),
                    'trending' => CommunityDiscussionResource::collection($this->trendingDiscussions()),
                    'stats' => [
                        'total_discussions' => CommunityDiscussion::where('status', 'published')->count(),
                        'total_replies' => \App\Core\Models\CommunityDiscussionReply::count(),
                    ],
                ],
            ]);
    }

    public function categories()
    {
        return response()->json([
            'data' => $this->categorySummary(),
        ]);
    }

    public function show(string $discussion)
    {
        $discussionModel = $this->findPublishedDiscussion($discussion)
            ->load(['category', 'user', 'replies.user.roles']);

        $discussionModel->increment('views_count');
        $discussionModel->refresh()->load(['category', 'user', 'replies.user.roles']);

        return new CommunityDiscussionResource($discussionModel);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'category_id' => ['nullable', 'integer', 'exists:community_discussion_categories,id'],
            'category_slug' => ['nullable', 'string', 'exists:community_discussion_categories,slug'],
            'tag' => ['nullable', 'string', 'max:40'],
        ]);

        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId && !empty($validated['category_slug'])) {
            $categoryId = CommunityDiscussionCategory::where('slug', $validated['category_slug'])->value('id');
        }

        $user = $request->user();
        $body = trim($validated['body']);
        $discussion = CommunityDiscussion::create([
            'category_id' => $categoryId,
            'user_id' => $user?->id,
            'author_name' => null,
            'title' => trim($validated['title']),
            'slug' => $this->uniqueSlug($validated['title']),
            'tag' => $validated['tag'] ?? 'Discussion',
            'excerpt' => Str::limit(strip_tags($body), 150),
            'body' => $body,
            'status' => 'published',
            'last_reply_at' => now(),
        ]);

        $discussion->load(['category', 'user']);

        return (new CommunityDiscussionResource($discussion))
            ->response()
            ->setStatusCode(201);
    }

    public function reply(Request $request, string $discussion)
    {
        $discussionModel = $this->findPublishedDiscussion($discussion);

        abort_if($discussionModel->is_locked, 423, 'This discussion is locked.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:8000'],
            'attachment_name' => ['nullable', 'string', 'max:180'],
        ]);

        $user = $request->user();
        $reply = CommunityDiscussionReply::create([
            'discussion_id' => $discussionModel->id,
            'user_id' => $user?->id,
            'author_name' => null,
            'body' => trim($validated['body']),
            'attachment_name' => $validated['attachment_name'] ?? null,
        ]);

        $discussionModel->forceFill([
            'replies_count' => $discussionModel->replies_count + 1,
            'last_reply_at' => now(),
            'last_reply_user_id' => $user?->id,
        ])->save();

        $reply->load('user.roles');

        return (new CommunityDiscussionReplyResource($reply))
            ->response()
            ->setStatusCode(201);
    }

    private function findPublishedDiscussion(string $value): CommunityDiscussion
    {
        return CommunityDiscussion::query()
            ->where('status', 'published')
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'discussion';
        $slug = $base;
        $suffix = 2;

        while (CommunityDiscussion::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categorySummary(): array
    {
        return CommunityDiscussionCategory::query()
            ->withCount([
                'discussions' => fn ($query) => $query->where('status', 'published'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CommunityDiscussionCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'color' => $category->color,
                'count' => $category->discussions_count,
            ])
            ->all();
    }

    private function trendingDiscussions()
    {
        return CommunityDiscussion::query()
            ->with(['category', 'user'])
            ->where('status', 'published')
            ->orderByDesc('replies_count')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();
    }
}
