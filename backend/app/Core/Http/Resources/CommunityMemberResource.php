<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommunityMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $displayName = $this->username ?: $this->name ?: '';
        $roles = $this->relationLoaded('roles') ? $this->roles->pluck('name') : collect();
        $settings = $this->relationLoaded('settings') ? $this->settings : null;
        $showOnline = $settings?->show_online ?? true;
        $isOnline = $showOnline && $this->last_active && $this->last_active->greaterThanOrEqualTo(now()->subMinutes(15));
        $isModerator = $roles->contains(fn (string $role) => in_array($role, ['admin', 'moderator'], true));
        $roleLabel = $roles->first(fn (string $role) => $role !== 'user') ?: $roles->first();

        return [
            'id' => $this->id,
            'display_name' => $displayName,
            'username' => $this->username ?? $displayName,
            'slug' => $this->username ?: (string) $this->id,
            'initial' => $this->initials($displayName),
            'color' => $this->colorFor($displayName),
            'role_label' => $roleLabel ? Str::headline($roleLabel) : '',
            'group_label' => $roleLabel ? Str::headline($roleLabel) : '',
            'badge_label' => $isModerator ? 'Moderator' : null,
            'bio' => $this->bio,
            'location' => $this->location,
            'website_url' => $this->website_url,
            'is_online' => $isOnline,
            'is_verified' => $this->email_verified_at !== null,
            'is_moderator' => $isModerator,
            'joined_at' => $this->created_at?->toIso8601String(),
            'joined_label' => $this->created_at?->format('M j, Y'),
            'last_active_at' => $this->last_active?->toIso8601String(),
            'last_active_label' => $isOnline ? 'Online now' : ($this->last_active?->diffForHumans() ?? ''),
            'interests' => [],
            'stats' => $this->stats(),
            'badges' => $roles->map(fn (string $role) => Str::headline($role))->values()->all(),
            'activities' => $this->community_activities ?? [],
            'href' => '/members/' . ($this->username ?: $this->id),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'posts' => (int) (($this->community_discussions_count ?? 0) + ($this->community_discussion_replies_count ?? 0)),
            'discussions' => (int) ($this->community_discussions_count ?? 0),
            'replies' => (int) ($this->community_discussion_replies_count ?? 0),
            'reviews' => (int) ($this->community_vendor_reviews_count ?? 0),
            'lab_reports' => (int) ($this->community_lab_results_count ?? 0),
            'likes' => 0,
            'solutions' => 0,
            'reputation' => 0,
        ];
    }

    private function initials(string $value): string
    {
        $parts = collect(preg_split('/\s+/', trim($value)) ?: [])->filter();

        if ($parts->count() > 1) {
            return strtoupper($parts->take(2)->map(fn (string $part) => substr($part, 0, 1))->join(''));
        }

        return strtoupper(substr($value, 0, 1));
    }

    private function colorFor(string $value): string
    {
        $colors = ['purple', 'blue', 'green', 'pink', 'orange', 'teal'];

        return $colors[abs(crc32($value)) % count($colors)] ?? 'purple';
    }
}
