<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityContentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'category_slug' => $this->category_slug,
            'tag' => $this->tag,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'icon' => $this->icon,
            'image_index' => $this->image_index,
            'image_url' => $this->image_url,
            'read_minutes' => $this->read_minutes,
            'read_label' => "{$this->read_minutes} min",
            'views' => $this->views_count,
            'downloads' => $this->downloads_count,
            'comments' => $this->comments_count,
            'metadata' => $this->metadata ?? [],
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_label' => $this->published_at?->format('M j, Y'),
            'time_ago' => $this->published_at?->diffForHumans(),
            'author' => [
                'name' => $this->displayAuthorName(),
                'badge' => $this->author_badge,
                'initial' => strtoupper(substr($this->displayAuthorName(), 0, 2)),
            ],
            'href' => match ($this->type) {
                'guide' => "/guides/{$this->slug}",
                'faq' => "/guides#faq-{$this->slug}",
                default => "/research-library/{$this->slug}",
            },
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
