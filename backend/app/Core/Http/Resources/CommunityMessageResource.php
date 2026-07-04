<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'side' => $userId && $this->sender_user_id === $userId ? 'out' : 'in',
            'body' => $this->body,
            'attachment_name' => $this->attachment_name,
            'attachment_meta' => $this->attachment_meta ?? [],
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sent_label' => $this->sent_at?->format('M j, Y, g:i A'),
            'time_ago' => $this->sent_at?->diffForHumans(),
            'sender' => $this->sender ? new CommunityMemberResource($this->sender) : null,
        ];
    }
}
