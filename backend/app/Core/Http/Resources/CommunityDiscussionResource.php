<?php

namespace App\Core\Http\Resources;

use App\Core\Models\CommunityDiscussionVote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommunityDiscussionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authorName = $this->displayAuthorName();
        $userId = $request->user()?->id;
        $attributes = $this->resource->getAttributes();
        $voteScore = $attributes['community_vote_score'] ?? null;
        $viewerVote = $attributes['community_viewer_vote'] ?? null;

        if ($voteScore === null || ($userId && $viewerVote === null)) {
            $votes = CommunityDiscussionVote::query()
                ->where('target_type', 'discussion')
                ->where('target_id', $this->id);
            $voteScore ??= (int) (clone $votes)->sum('value');
            $viewerVote ??= $userId
                ? (clone $votes)->where('user_id', $userId)->value('value')
                : 0;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'tag' => $this->tag,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'status' => $this->status,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'premium_only' => $this->premium_only ?? false,
            'replies' => $this->replies_count,
            'views' => $this->views_count,
            'vote_score' => (int) $voteScore,
            'viewer_vote' => (int) ($viewerVote ?? 0),
            'href' => "/discussions/{$this->slug}",
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
                'color' => $this->category->color,
            ] : null,
            'author' => [
                'id' => $this->user_id,
                'name' => $authorName,
                'username' => $this->user?->username,
                'initial' => Str::upper(Str::substr($authorName, 0, 1)),
                'avatar' => $this->user?->profile_picture ?? $this->user?->profile_photo_path ?? null,
                'is_online' => $this->user?->last_active && $this->user->last_active->gt(now()->subMinutes(15)),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'last_reply_at' => $this->last_reply_at?->toIso8601String(),
            'time_ago' => $this->created_at?->diffForHumans(),
            'last_activity' => $this->last_reply_at?->diffForHumans() ?? $this->updated_at?->diffForHumans(),
            'reply_items' => CommunityDiscussionReplyResource::collection($this->whenLoaded('replies')),
            'participants' => $this->community_participants ?? [],
            'similar_discussions' => $this->community_similar_discussions ?? [],
            'last_reply' => $this->last_reply_at && $this->relationLoaded('lastReplyUser') && $this->lastReplyUser ? [
                'author' => $this->lastReplyUser->name ?? $this->lastReplyUser->username ?? 'Unknown',
                'username' => $this->lastReplyUser->username,
                'time_ago' => $this->last_reply_at->diffForHumans(),
                'avatar' => $this->lastReplyUser->profile_picture ?? $this->lastReplyUser->profile_photo_path ?? null,
                'initial' => Str::upper(Str::substr($this->lastReplyUser->name ?? $this->lastReplyUser->username ?? 'U', 0, 1)),
            ] : null,
        ];
    }
}
