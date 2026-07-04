<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasReadState = $this->relationLoaded('reads');
        $read = $hasReadState ? $this->reads->first() : null;
        $readAt = $read?->read_at;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'category_slug' => $this->category_slug,
            'icon' => $this->icon,
            'tone' => $this->tone,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_url' => $this->source_url,
            'views' => $this->views_count,
            'is_pinned' => $this->is_pinned,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_label' => $this->published_at?->format('M j, Y · h:i A'),
            'time_ago' => $this->published_at?->diffForHumans(),
            'read_at' => $readAt?->toIso8601String(),
            'unread' => $hasReadState ? $readAt === null : false,
            'detail_href' => "/notifications/{$this->slug}",
            'href' => $this->source_url ?: "/notifications/{$this->slug}",
            'author' => [
                'name' => $this->displayAuthorName(),
                'initial' => strtoupper(substr($this->displayAuthorName(), 0, 1)),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
