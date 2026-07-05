<?php

namespace App\Core\Http\Resources;

use App\Core\Models\CommunityDiscussionVote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommunityDiscussionReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authorName = $this->displayAuthorName();
        $roleName = $this->user?->relationLoaded('roles')
            ? $this->user?->roles?->pluck('name')->first()
            : null;
        $userId = $request->user()?->id;
        $votes = CommunityDiscussionVote::query()
            ->where('target_type', 'reply')
            ->where('target_id', $this->id);
        $viewerVote = $userId
            ? (clone $votes)->where('user_id', $userId)->value('value')
            : null;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'attachment_name' => $this->attachment_name,
            'attachment_url' => $this->attachment_url,
            'attachment_meta' => $this->attachment_meta ?? [],
            'votes' => (int) (clone $votes)->sum('value'),
            'viewer_vote' => (int) ($viewerVote ?? 0),
            'is_solution' => $this->is_solution,
            'author' => [
                'id' => $this->user_id,
                'name' => $authorName,
                'username' => $this->user?->username,
                'initial' => Str::upper(Str::substr($authorName, 0, 1)),
                'badge' => $roleName ? Str::headline($roleName) : null,
                'avatar' => $this->user?->profile_picture ?? $this->user?->profile_photo_path ?? null,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'time_ago' => $this->created_at?->diffForHumans(),
        ];
    }
}
