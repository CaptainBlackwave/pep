<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityMessageThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastMessage = $this->messages?->sortByDesc('sent_at')->first();

        return [
            'id' => $this->id,
            'participant' => new CommunityMemberResource($this->whenLoaded('participant')),
            'unread_count' => $this->unread_count,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_label' => $this->last_message_at?->diffForHumans(),
            'preview' => $lastMessage?->body,
            'messages' => CommunityMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
