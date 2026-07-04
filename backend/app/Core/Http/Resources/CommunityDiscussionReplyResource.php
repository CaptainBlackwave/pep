<?php

namespace App\Core\Http\Resources;

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

        return [
            'id' => $this->id,
            'body' => $this->body,
            'attachment_name' => $this->attachment_name,
            'votes' => $this->votes_count,
            'is_solution' => $this->is_solution,
            'author' => [
                'id' => $this->user_id,
                'name' => $authorName,
                'username' => $this->user?->username,
                'initial' => Str::upper(Str::substr($authorName, 0, 1)),
                'badge' => $roleName ? Str::headline($roleName) : null,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'time_ago' => $this->created_at?->diffForHumans(),
        ];
    }
}
