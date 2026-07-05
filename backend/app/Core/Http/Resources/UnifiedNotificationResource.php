<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnifiedNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $source = $this->resource['_source'] ?? 'community';
        $isUnread = $source === 'system' ? $this->isUnread() : !$this->reads?->contains('user_id', $request->user()?->id);

        $icon = $source === 'system'
            ? ($this->icon ?? 'bell')
            : ($this->icon ?? 'megaphone');

        $category = $source === 'system'
            ? ($this->type === 'message' ? 'Messages' : 'System')
            : ($this->category ?? 'Update');

        $categorySlug = $source === 'system'
            ? ($this->type === 'message' ? 'messages' : 'system')
            : ($this->category_slug ?? 'update');

        $url = $source === 'system'
            ? ($this->link ?? '/')
            : $this->href();

        $time = $source === 'system'
            ? $this->created_at
            : $this->published_at;

        return [
            'id' => "{$source}_{$this->id}",
            'source' => $source,
            'type' => $source === 'system' ? $this->type : 'community',
            'title' => $source === 'system' ? $this->title : $this->title,
            'text' => $source === 'system' ? ($this->message ?? '') : ($this->body ?? $this->excerpt ?? ''),
            'body' => $source === 'system' ? ($this->message ?? '') : ($this->body ?? ''),
            'icon' => $icon,
            'category' => $category,
            'category_slug' => $categorySlug,
            'unread' => $isUnread,
            'href' => $url,
            'detail_href' => $source === 'system' ? $url : "/notifications/{$this->slug}",
            'slug' => $source === 'system' ? (string) $this->id : ($this->slug ?? (string) $this->id),
            'time' => $time?->diffForHumans(),
            'date' => $time?->toIso8601String(),
            'time_label' => $time?->diffForHumans(),
            'author' => null,
            'views' => 0,
            'source_id' => $this->id,
            'tone' => 'info',
            'created_at' => $time?->toIso8601String(),
        ];
    }
}
