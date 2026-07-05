<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CommunityUserAction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityUserActionController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $this->actionPayload($request->user()->id),
        ]);
    }

    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['follow', 'save', 'bookmark'])],
            'target_type' => ['required', Rule::in(['discussion', 'content', 'member'])],
            'target_key' => ['required', 'string', 'max:240'],
        ]);

        $row = CommunityUserAction::query()
            ->where('user_id', $request->user()->id)
            ->where('action', $validated['action'])
            ->where('target_type', $validated['target_type'])
            ->where('target_key', $validated['target_key'])
            ->first();

        if ($row) {
            $row->delete();
            $active = false;
        } else {
            CommunityUserAction::create([
                'user_id' => $request->user()->id,
                'action' => $validated['action'],
                'target_type' => $validated['target_type'],
                'target_key' => $validated['target_key'],
            ]);
            $active = true;
        }

        return response()->json([
            'active' => $active,
            'data' => $this->actionPayload($request->user()->id),
        ]);
    }

    private function actionPayload(int $userId): array
    {
        $actions = CommunityUserAction::query()
            ->where('user_id', $userId)
            ->get(['action', 'target_type', 'target_key']);

        return [
            'followed_discussions' => $actions
                ->where('action', 'follow')
                ->where('target_type', 'discussion')
                ->pluck('target_key')
                ->values()
                ->all(),
            'saved_discussions' => $actions
                ->where('action', 'save')
                ->where('target_type', 'discussion')
                ->pluck('target_key')
                ->values()
                ->all(),
            'bookmarked_content' => $actions
                ->where('action', 'bookmark')
                ->where('target_type', 'content')
                ->pluck('target_key')
                ->values()
                ->all(),
            'followed_members' => $actions
                ->where('action', 'follow')
                ->where('target_type', 'member')
                ->pluck('target_key')
                ->values()
                ->all(),
        ];
    }
}
