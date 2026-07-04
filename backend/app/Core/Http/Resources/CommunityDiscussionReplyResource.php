<?php

namespace App\Core\Http\Resources;

use App\Core\Models\CommunityDiscussionReaction;
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
        $reactions = CommunityDiscussionReaction::query()
            ->where('target_type', 'reply')
            ->where('target_id', $this->id)
            ->get();
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'attachment_name' => $this->attachment_name,
            'attachment_url' => $this->attachment_url,
            'attachment_meta' => $this->attachment_meta ?? [],
            'votes' => $this->votes_count,
            'is_solution' => $this->is_solution,
            'reactions' => $reactions
                ->groupBy('emoji')
                ->map(fn ($items) => $items->count())
                ->all(),
            'viewer_reactions' => $userId
                ? $reactions->where('user_id', $userId)->pluck('emoji')->values()->all()
                : [],
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
