<?php

namespace App\Plugins\ContentLibrary\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityContentItemResource;
use App\Core\Models\CommunityContentItem;
use App\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityContentContributorController extends Controller
{
    /** @var array<int, string> */
    private array $contributorRoles = ['admin', 'moderator', 'staff', 'editor', 'content-editor', 'researcher'];

    /** @var array<int, string> */
    private array $publisherRoles = ['admin', 'moderator', 'editor', 'content-editor'];

    public function permissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'can_create' => $this->canCreate($user),
                'can_update' => $this->canUpdateOwn($user),
                'can_publish' => $this->canPublish($user),
                'can_manage' => $this->canManageAny($user),
                'default_status' => $this->canPublish($user) ? 'published' : 'draft',
                'allowed_statuses' => $this->allowedStatuses($user),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeContributor($request->user());

        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['all', 'research', 'guide', 'faq'])],
            'status' => ['nullable', Rule::in(['all', 'draft', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $query = $this->visibleContentQuery($request->user());

        if (($validated['type'] ?? 'all') !== 'all') {
            $query->where('type', $validated['type']);
        }

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 40);

        return CommunityContentItemResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'permissions' => [
                    'can_create' => $this->canCreate($request->user()),
                    'can_update' => $this->canUpdateOwn($request->user()),
                    'can_publish' => $this->canPublish($request->user()),
                    'can_manage' => $this->canManageAny($request->user()),
                    'allowed_statuses' => $this->allowedStatuses($request->user()),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeCreate($request->user());

        $validated = $this->validateContent($request);
        $status = $this->normalizeStatus($request->user(), $validated['status'] ?? 'draft');

        $item = CommunityContentItem::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['type'], $validated['slug'] ?? $validated['title']),
            'category_slug' => $validated['category_slug'] ?? Str::slug($validated['category'] ?? 'General'),
            'user_id' => $request->user()?->id,
            'status' => $status,
            'published_at' => $validated['published_at'] ?? ($status === 'published' ? now() : null),
        ]);

        return (new CommunityContentItemResource($item->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $content): CommunityContentItemResource
    {
        $this->authorizeContributor($request->user());

        return new CommunityContentItemResource($this->findVisibleContent($request->user(), $content));
    }

    public function update(Request $request, string $content): CommunityContentItemResource
    {
        $item = $this->findVisibleContent($request->user(), $content);
        $this->authorizeUpdate($request->user(), $item);

        $validated = $this->validateContent($request, true);

        if (isset($validated['status'])) {
            $validated['status'] = $this->normalizeStatus($request->user(), $validated['status']);
        }

        if (isset($validated['slug']) || isset($validated['title'])) {
            $validated['slug'] = $this->uniqueSlug(
                $validated['type'] ?? $item->type,
                $validated['slug'] ?? $validated['title'] ?? $item->slug,
                $item->id
            );
        }

        if (isset($validated['category']) && empty($validated['category_slug'])) {
            $validated['category_slug'] = Str::slug($validated['category']);
        }

        if (($validated['status'] ?? null) === 'published' && !$item->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if (($validated['status'] ?? null) !== 'published') {
            $validated['published_at'] = $validated['published_at'] ?? null;
        }

        $item->fill($validated)->save();

        return new CommunityContentItemResource($item->load('author'));
    }

    public function destroy(Request $request, string $content): JsonResponse
    {
        $item = $this->findVisibleContent($request->user(), $content);
        $this->authorizeUpdate($request->user(), $item);

        $item->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Content item hidden.',
        ]);
    }

    private function validateContent(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, Rule::in(['research', 'guide', 'faq'])],
            'title' => [$required, 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240'],
            'category' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:100'],
            'category_slug' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:80'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:50000'],
            'icon' => ['nullable', 'string', 'max:40'],
            'image_index' => ['nullable', 'integer', 'min:0', 'max:99'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'read_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'author_name' => ['nullable', 'string', 'max:140'],
            'author_badge' => ['nullable', 'string', 'max:80'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'hidden'])],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function visibleContentQuery(?User $user): Builder
    {
        $query = CommunityContentItem::query()->with('author');

        if (!$this->canManageAny($user)) {
            $query->where('user_id', $user?->id);
        }

        return $query;
    }

    private function findVisibleContent(?User $user, string $value): CommunityContentItem
    {
        return $this->visibleContentQuery($user)
            ->where(function (Builder $query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }

    private function uniqueSlug(string $type, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'content';
        $slug = $base;
        $suffix = 2;

        while (CommunityContentItem::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('type', $type)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function normalizeStatus(?User $user, string $status): string
    {
        if ($status === 'published' && !$this->canPublish($user)) {
            abort(403, 'You can save content drafts, but publishing requires content editor permission.');
        }

        if ($status === 'hidden' && !$this->canUpdateOwn($user)) {
            abort(403, 'You are not allowed to hide content.');
        }

        return $status;
    }

    private function authorizeContributor(?User $user): void
    {
        if (!$this->canCreate($user) && !$this->canUpdateOwn($user) && !$this->canManageAny($user)) {
            abort(403, 'You are not allowed to manage content.');
        }
    }

    private function authorizeCreate(?User $user): void
    {
        if (!$this->canCreate($user)) {
            abort(403, 'You are not allowed to create content.');
        }
    }

    private function authorizeUpdate(?User $user, CommunityContentItem $item): void
    {
        if ($this->canManageAny($user)) {
            return;
        }

        if ($item->user_id === $user?->id && $this->canUpdateOwn($user)) {
            return;
        }

        abort(403, 'You are not allowed to update this content.');
    }

    private function canCreate(?User $user): bool
    {
        return $this->hasAnyRole($user, $this->contributorRoles)
            || $this->hasPermission($user, ['community-content.create', 'community-content.manage']);
    }

    private function canUpdateOwn(?User $user): bool
    {
        return $this->hasAnyRole($user, $this->contributorRoles)
            || $this->hasPermission($user, ['community-content.update', 'community-content.manage']);
    }

    private function canPublish(?User $user): bool
    {
        return $this->hasAnyRole($user, $this->publisherRoles)
            || $this->hasPermission($user, ['community-content.publish', 'community-content.manage']);
    }

    private function canManageAny(?User $user): bool
    {
        return $this->hasAnyRole($user, ['admin', 'moderator', 'content-editor'])
            || $this->hasPermission($user, ['community-content.manage']);
    }

    /**
     * @param array<int, string> $roles
     */
    private function hasAnyRole(?User $user, array $roles): bool
    {
        return $user ? $user->hasAnyRole($roles) : false;
    }

    /**
     * @param array<int, string> $permissions
     */
    private function hasPermission(?User $user, array $permissions): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function allowedStatuses(?User $user): array
    {
        return $this->canPublish($user)
            ? ['draft', 'published', 'hidden']
            : ['draft', 'hidden'];
    }
}
