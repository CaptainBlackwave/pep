<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionReply;
use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class CommunityModerateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|moderator');
    }

    public function updateDiscussion(Request $request, string $discussion)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
            'is_pinned' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
        ]);

        $discussionModel = CommunityDiscussion::query()
            ->where(function ($query) use ($discussion) {
                $query->where('slug', $discussion);
                if (ctype_digit($discussion)) {
                    $query->orWhere('id', (int) $discussion);
                }
            })
            ->firstOrFail();

        $discussionModel->fill($validated);
        $discussionModel->save();

        return response()->json([
            'success' => true,
            'message' => 'Discussion updated.',
        ]);
    }

    public function deleteDiscussion(Request $request, string $discussion)
    {
        $discussionModel = CommunityDiscussion::query()
            ->where(function ($query) use ($discussion) {
                $query->where('slug', $discussion);
                if (ctype_digit($discussion)) {
                    $query->orWhere('id', (int) $discussion);
                }
            })
            ->firstOrFail();

        $discussionModel->forceFill(['status' => 'hidden', 'is_locked' => true])->save();

        return response()->json([
            'success' => true,
            'message' => 'Discussion hidden.',
        ]);
    }

    public function deleteReply(Request $request, string $reply)
    {
        $replyModel = CommunityDiscussionReply::query()->findOrFail($reply);
        $replyModel->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Reply hidden.',
        ]);
    }

    public function banUser(Request $request, string $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $userModel = User::query()->findOrFail($user);
        $userModel->update(['is_banned' => true]);

        return response()->json([
            'success' => true,
            'message' => 'User banned.',
        ]);
    }

    public function warnUser(Request $request, string $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $userModel = User::query()->findOrFail($user);

        return response()->json([
            'success' => true,
            'message' => 'User warned.',
        ]);
    }
}
