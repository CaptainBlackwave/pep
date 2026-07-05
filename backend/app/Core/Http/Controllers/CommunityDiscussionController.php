<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityDiscussionReplyResource;
use App\Core\Http\Resources\CommunityDiscussionResource;
use App\Core\Http\Resources\CommunityMemberResource;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionCategory;
use App\Core\Models\CommunityDiscussionReport;
use App\Core\Models\CommunityDiscussionReply;
use App\Core\Models\CommunityDiscussionVote;
use App\Core\Models\DiscussionSubscription;
use App\Core\Services\NotificationService;
use App\Core\Services\PushNotificationService;
use App\Core\Services\WebSocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CommunityDiscussionController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private WebSocketService $websocket,
        private PushNotificationService $push,
    ) {
    }

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
            ->paginate($limit)
            ->withQueryString();
        $this->hydrateVoteAttributes($discussions->getCollection(), 'discussion', $request->user()?->id);

        return CommunityDiscussionResource::collection($discussions)
            ->additional([
                'meta' => [
                    'categories' => $this->categorySummary(),
                    'trending' => CommunityDiscussionResource::collection($this->trendingDiscussions($request->user()?->id)),
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
            ->load(['category', 'user.roles', 'user.settings', 'replies.user.roles', 'replies.user.settings']);

        $discussionModel->increment('views_count');
        $discussionModel->refresh()->load(['category', 'user.roles', 'user.settings', 'replies.user.roles', 'replies.user.settings']);
        $this->hydrateVoteAttributes(collect([$discussionModel]), 'discussion', request()->user()?->id);
        $this->hydrateVoteAttributes($discussionModel->replies, 'reply', request()->user()?->id);

        $participants = collect([$discussionModel->user])
            ->merge($discussionModel->replies->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();

        $discussionModel->setAttribute(
            'community_participants',
            CommunityMemberResource::collection($participants)->resolve(request())
        );

        $similarDiscussions = CommunityDiscussion::query()
            ->with(['category', 'user'])
            ->where('status', 'published')
            ->whereKeyNot($discussionModel->id)
            ->when(
                $discussionModel->category_id,
                fn ($query) => $query->where('category_id', $discussionModel->category_id)
            )
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
        $this->hydrateVoteAttributes($similarDiscussions, 'discussion', request()->user()?->id);

        $discussionModel->setAttribute(
            'community_similar_discussions',
            CommunityDiscussionResource::collection($similarDiscussions)->resolve(request())
        );

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

        DiscussionSubscription::firstOrCreate([
            'discussion_id' => $discussion->id,
            'user_id' => $user->id,
        ]);

        $discussion->load(['category', 'user']);

        $this->notifyMentionedUsers($body, $discussion, $user);

        return (new CommunityDiscussionResource($discussion))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $discussion)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'tag' => ['nullable', 'string', 'max:40'],
        ]);

        $discussionModel = $this->findOwnDiscussion($request, $discussion);
        $body = trim($validated['body']);

        $discussionModel->update([
            'title' => trim($validated['title']),
            'body' => $body,
            'excerpt' => Str::limit(strip_tags($body), 150),
            'tag' => $validated['tag'] ?? $discussionModel->tag,
        ]);

        $discussionModel->load(['category', 'user']);

        return new CommunityDiscussionResource($discussionModel);
    }

    public function destroy(Request $request, string $discussion)
    {
        $discussionModel = $this->findOwnDiscussion($request, $discussion);
        $discussionModel->update(['status' => 'hidden']);

        return response()->noContent();
    }

    public function reply(Request $request, string $discussion)
    {
        $discussionModel = $this->findPublishedDiscussion($discussion);

        abort_if($discussionModel->is_locked, 423, 'This discussion is locked.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:8000'],
            'attachment_name' => ['nullable', 'string', 'max:180'],
            'attachment_url' => ['nullable', 'url', 'max:2048'],
            'attachment_type' => ['nullable', Rule::in(['image', 'gif', 'file'])],
            'attachment_size' => ['nullable', 'integer', 'min:0', 'max:10485760'],
            'attachment' => ['nullable', 'file', 'max:8192', 'mimes:jpg,jpeg,png,gif,webp'],
        ]);
        $attachment = $this->resolveReplyAttachment($request, $validated);

        $user = $request->user();
        $reply = CommunityDiscussionReply::create([
            'discussion_id' => $discussionModel->id,
            'user_id' => $user?->id,
            'author_name' => null,
            'body' => trim($validated['body']),
            'attachment_name' => $attachment['name'],
            'attachment_url' => $attachment['url'],
            'attachment_meta' => $attachment['meta'],
        ]);

        $discussionModel->forceFill([
            'replies_count' => $discussionModel->replies_count + 1,
            'last_reply_at' => now(),
            'last_reply_user_id' => $user?->id,
        ])->save();

        DiscussionSubscription::firstOrCreate([
            'discussion_id' => $discussionModel->id,
            'user_id' => $user->id,
        ]);

        $subscribers = DiscussionSubscription::query()
            ->where('discussion_id', $discussionModel->id)
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get();
        $senderName = $this->displayNameForUser($user);

        foreach ($subscribers as $sub) {
            $this->notifications->create(
                user: $sub->user,
                type: 'discussion_reply',
                title: "New reply in {$discussionModel->title}",
                message: Str::limit($validated['body'], 200),
                data: ['discussion_id' => $discussionModel->id, 'reply_id' => $reply->id],
                icon: '💬',
                link: "/discussions/{$discussionModel->slug}",
            );
            $this->websocket->toUser($sub->user, 'notification', [
                'type' => 'discussion_reply',
                'title' => $discussionModel->title,
                'message' => "New reply from {$senderName}",
            ]);
            $this->push->send(
                $sub->user,
                "New reply in {$discussionModel->title}",
                "{$senderName}: " . Str::limit($validated['body'], 100),
                "/discussions/{$discussionModel->slug}",
            );
        }

        $this->notifyMentionedUsers($validated['body'], $discussionModel, $user);

        $reply->load('user.roles');

        return (new CommunityDiscussionReplyResource($reply))
            ->response()
            ->setStatusCode(201);
    }

    public function voteOnDiscussion(Request $request, string $discussion)
    {
        $discussionModel = $this->findPublishedDiscussion($discussion);
        $this->toggleVote($request, 'discussion', $discussionModel->id);

        $discussionModel->load(['category', 'user.roles', 'user.settings']);

        return new CommunityDiscussionResource($discussionModel);
    }

    public function voteOnReply(Request $request, string $reply)
    {
        $replyModel = $this->findPublishedReply($reply);
        $this->toggleVote($request, 'reply', $replyModel->id);

        $replyModel->load('user.roles');

        return new CommunityDiscussionReplyResource($replyModel);
    }

    public function reportDiscussion(Request $request, string $discussion)
    {
        $discussionModel = $this->findPublishedDiscussion($discussion);
        $this->createReport($request, 'discussion', $discussionModel->id);

        return response()->json([
            'message' => 'Report submitted for moderator review.',
        ], 201);
    }

    public function reportReply(Request $request, string $reply)
    {
        $replyModel = $this->findPublishedReply($reply);
        $this->createReport($request, 'reply', $replyModel->id);

        return response()->json([
            'message' => 'Report submitted for moderator review.',
        ], 201);
    }

    private function findOwnDiscussion(Request $request, string $value): CommunityDiscussion
    {
        return CommunityDiscussion::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
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

    public function destroyReply(Request $request, string $reply)
    {
        $user = $request->user();
        $replyModel = CommunityDiscussionReply::with('discussion')->findOrFail($reply);

        abort_unless(
            $replyModel->user_id === $user->id || $user->hasRole('admin') || $user->hasRole('moderator'),
            403,
            'You can only delete your own replies.'
        );

        $discussion = $replyModel->discussion;
        if ($discussion) {
            $discussion->decrement('replies_count');
        }

        $replyModel->update([
            'body' => '[deleted]',
            'user_id' => null,
            'author_name' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Reply deleted.']);
    }

    private function findPublishedReply(string $value): CommunityDiscussionReply
    {
        return CommunityDiscussionReply::query()
            ->whereHas('discussion', fn ($query) => $query->where('status', 'published'))
            ->where(function ($query) use ($value) {
                if (ctype_digit($value)) {
                    $query->where('id', (int) $value);
                } else {
                    $query->whereRaw('0 = 1');
                }
            })
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name: string|null, url: string|null, meta: array<string, mixed>|null}
     */
    private function resolveReplyAttachment(Request $request, array $validated): array
    {
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('community/discussion-replies', 'public');
            $mime = $file->getMimeType() ?: 'application/octet-stream';

            return [
                'name' => $file->getClientOriginalName(),
                'url' => Storage::disk('public')->url($path),
                'meta' => [
                    'type' => $mime === 'image/gif' ? 'gif' : 'image',
                    'mime' => $mime,
                    'size' => $file->getSize(),
                ],
            ];
        }

        if (!empty($validated['attachment_url'])) {
            $url = (string) $validated['attachment_url'];
            $name = $validated['attachment_name'] ?? basename(parse_url($url, PHP_URL_PATH) ?: 'attachment');
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            $type = $validated['attachment_type'] ?? ($extension === 'gif' ? 'gif' : 'image');

            return [
                'name' => $name ?: 'attachment',
                'url' => $url,
                'meta' => [
                    'type' => $type,
                    'size' => $validated['attachment_size'] ?? null,
                ],
            ];
        }

        if (!empty($validated['attachment_name'])) {
            return [
                'name' => (string) $validated['attachment_name'],
                'url' => null,
                'meta' => [
                    'type' => $validated['attachment_type'] ?? 'file',
                    'size' => $validated['attachment_size'] ?? null,
                ],
            ];
        }

        return [
            'name' => null,
            'url' => null,
            'meta' => null,
        ];
    }

    private function toggleVote(Request $request, string $targetType, int $targetId): void
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([-1, 1])],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $value = (int) $validated['value'];
        $vote = CommunityDiscussionVote::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('user_id', $user->id)
            ->first();

        if ($vote && (int) $vote->value === $value) {
            $vote->delete();
            return;
        }

        CommunityDiscussionVote::updateOrCreate(
            [
                'target_type' => $targetType,
                'target_id' => $targetId,
                'user_id' => $user->id,
            ],
            ['value' => $value],
        );
    }

    private function createReport(Request $request, string $targetType, int $targetId): void
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(['spam', 'harassment', 'scam', 'source-discussion', 'privacy', 'medical-claims', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        CommunityDiscussionReport::create([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'user_id' => $request->user()?->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'open',
        ]);
    }

    private function notifyMentionedUsers(string $body, CommunityDiscussion $discussion, $sender): void
    {
        preg_match_all('/@([A-Za-z0-9_]{3,40})/', $body, $matches);
        $usernames = array_unique($matches[1] ?? []);

        if (empty($usernames)) {
            return;
        }

        $mentioned = \App\Core\Models\User::query()
            ->whereIn('username', $usernames)
            ->where('id', '!=', $sender->id)
            ->get();
        $senderName = $this->displayNameForUser($sender);

        foreach ($mentioned as $user) {
            $this->notifications->create(
                user: $user,
                type: 'discussion_mention',
                title: "You were mentioned in {$discussion->title}",
                message: Str::limit($body, 200),
                data: ['discussion_id' => $discussion->id],
                icon: '💬',
                link: "/discussions/{$discussion->slug}",
            );
            $this->websocket->toUser($user, 'notification', [
                'type' => 'discussion_mention',
                'title' => $discussion->title,
                'message' => "You were mentioned by {$senderName}",
            ]);
            $this->push->send($user, "Mentioned by {$senderName}", Str::limit($body, 100), "/discussions/{$discussion->slug}");
        }
    }

    private function displayNameForUser($user): string
    {
        if (!$user?->id) {
            return 'member';
        }

        $freshUser = \App\Core\Models\User::query()
            ->whereKey($user->id)
            ->first(['username', 'name']);

        return $freshUser?->username ?: $freshUser?->name ?: 'member';
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

    private function hydrateVoteAttributes($items, string $targetType, ?int $userId): void
    {
        $items = collect($items);
        $ids = $items->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $scores = CommunityDiscussionVote::query()
            ->where('target_type', $targetType)
            ->whereIn('target_id', $ids)
            ->selectRaw('target_id, COALESCE(SUM(value), 0) as score')
            ->groupBy('target_id')
            ->pluck('score', 'target_id');

        $viewerVotes = $userId
            ? CommunityDiscussionVote::query()
                ->where('target_type', $targetType)
                ->whereIn('target_id', $ids)
                ->where('user_id', $userId)
                ->pluck('value', 'target_id')
            : collect();

        foreach ($items as $item) {
            $item->setAttribute('community_vote_score', (int) ($scores[$item->id] ?? 0));
            $item->setAttribute('community_viewer_vote', (int) ($viewerVotes[$item->id] ?? 0));
        }
    }

    private function trendingDiscussions(?int $userId = null)
    {
        $discussions = CommunityDiscussion::query()
            ->with(['category', 'user'])
            ->where('status', 'published')
            ->orderByDesc('replies_count')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();
        $this->hydrateVoteAttributes($discussions, 'discussion', $userId);

        return $discussions;
    }
}
