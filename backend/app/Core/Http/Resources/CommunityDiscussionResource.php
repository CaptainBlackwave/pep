<?php

namespace App\Core\Http\Resources;

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
            'replies' => $this->replies_count,
            'views' => $this->views_count,
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
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'last_reply_at' => $this->last_reply_at?->toIso8601String(),
            'time_ago' => $this->created_at?->diffForHumans(),
            'last_activity' => $this->last_reply_at?->diffForHumans() ?? $this->updated_at?->diffForHumans(),
            'reply_items' => CommunityDiscussionReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
