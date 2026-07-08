<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CommunityDiscussionReply;
use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommunityModerateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|moderator');
    }

    public function deleteReply(Request $request, string $reply)
    {
        $replyModel = CommunityDiscussionReply::query()->with('discussion')->findOrFail($reply);
        $replyModel->forceFill(['status' => 'hidden'])->save();

        if ($replyModel->discussion) {
            app(\App\Core\Services\WebSocketService::class)->broadcast('discussions', 'reply.deleted', [
                'discussion_id' => $replyModel->discussion_id,
                'slug' => $replyModel->discussion->slug,
                'reply_id' => $replyModel->id,
            ]);
        }

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
        
        app(\App\Core\Services\ModerationService::class)->banPlayer(
            $userModel,
            $request->user(),
            ['type' => 'permanent', 'reason' => $validated['reason']]
        );

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
        
        app(\App\Core\Services\ModerationService::class)->warnPlayer(
            $userModel,
            $request->user(),
            ['severity' => 'medium', 'reason' => $validated['reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'User warned.',
        ]);
    }
}
