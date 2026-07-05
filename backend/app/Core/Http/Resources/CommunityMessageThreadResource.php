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
        $viewerId = $request->user()?->id;
        $otherParticipant = $viewerId && (int) $this->participant_user_id === (int) $viewerId
            ? $this->whenLoaded('owner')
            : $this->whenLoaded('participant');
        $lastMessage = $this->messages?->sortByDesc('sent_at')->first();
        $viewerUnreadCount = $viewerId && (int) $this->user_id === (int) $viewerId
            ? (int) ($this->owner_unread_count ?? $this->unread_count ?? 0)
            : (int) ($this->participant_unread_count ?? $this->unread_count ?? 0);

        return [
            'id' => $this->id,
            'participant' => new CommunityMemberResource($otherParticipant),
            'unread_count' => $viewerUnreadCount,
            'owner_user_id' => $this->user_id,
            'participant_user_id' => $this->participant_user_id,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_label' => $this->last_message_at?->diffForHumans(),
            'preview' => $lastMessage?->body,
            'messages' => CommunityMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
