<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityDiscussionResource;
use App\Core\Models\CommunityDiscussion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityDiscussionModerationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'published', 'hidden'])],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityDiscussion::query()
            ->with(['category', 'user'])
            ->withCount('replies');

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 50);

        return CommunityDiscussionResource::collection(
            $query->latest('updated_at')->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => CommunityDiscussion::count(),
                    'published' => CommunityDiscussion::where('status', 'published')->count(),
                    'hidden' => CommunityDiscussion::where('status', 'hidden')->count(),
                    'locked' => CommunityDiscussion::where('is_locked', true)->count(),
                    'pinned' => CommunityDiscussion::where('is_pinned', true)->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request, string $discussion)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
            'is_pinned' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
            'premium_only' => ['nullable', 'boolean'],
        ]);

        $discussionModel = $this->findDiscussion($discussion);
        $discussionModel->fill($validated);
        $discussionModel->save();
        $discussionModel->load(['category', 'user']);

        return new CommunityDiscussionResource($discussionModel);
    }

    public function destroy(string $discussion)
    {
        $discussionModel = $this->findDiscussion($discussion);
        $discussionModel->forceFill([
            'status' => 'hidden',
            'is_locked' => true,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Discussion hidden and locked.',
        ]);
    }

    private function findDiscussion(string $value): CommunityDiscussion
    {
        return CommunityDiscussion::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);

                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->firstOrFail();
    }
}
