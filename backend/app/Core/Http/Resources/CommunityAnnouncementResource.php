<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityAnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'image_url' => $this->image_url,
            'comments' => $this->comments_count,
            'views' => $this->views_count,
            'is_pinned' => $this->is_pinned,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_label' => $this->published_at?->format('M j, Y · h:i A'),
            'time_ago' => $this->published_at?->diffForHumans(),
            'href' => "/announcements/{$this->slug}",
            'author' => [
                'name' => $this->displayAuthorName(),
                'initial' => strtoupper(substr($this->displayAuthorName(), 0, 1)),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
