<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityMemberResource;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityLabResult;
use App\Core\Models\CommunityVendorReview;
use App\Core\Models\User;
use Illuminate\Http\Request;

class CommunityMemberController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'online' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $query = $this->memberQuery();

        if (array_key_exists('online', $validated)) {
            $this->applyOnlineFilter($query, (bool) $validated['online']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $limit = (int) ($validated['limit'] ?? 40);
        $statsQuery = $this->memberQuery();

        return CommunityMemberResource::collection(
            $query->orderByDesc('last_active')->latest()->limit($limit)->get()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => (clone $statsQuery)->count(),
                    'online' => tap(clone $statsQuery, fn ($query) => $this->applyOnlineFilter($query, true))->count(),
                ],
            ],
        ]);
    }

    public function show(string $member)
    {
        $user = $this->memberQuery()
            ->where(function ($query) use ($member) {
                $query->where('username', $member)->orWhere('name', $member);

                if (ctype_digit($member)) {
                    $query->orWhere('id', (int) $member);
                }
            })
            ->firstOrFail();

        $user->setAttribute('community_activities', $this->activitiesFor($user));

        return new CommunityMemberResource($user);
    }

    private function memberQuery()
    {
        return User::query()
            ->with(['roles', 'settings'])
            ->withCount([
                'communityDiscussions',
                'communityDiscussionReplies',
                'communityLabResults',
                'communityVendorReviews',
            ])
            ->where(function ($query) {
                $query
                    ->whereDoesntHave('settings')
                    ->orWhereHas('settings', fn ($settings) => $settings
                        ->where('public_profile', true)
                        ->where('profile_visibility', '!=', 'nobody'));
            });
    }

    private function applyOnlineFilter($query, bool $online): void
    {
        if ($online) {
            $query
                ->where('last_active', '>=', now()->subMinutes(15))
                ->where(function ($inner) {
                    $inner
                        ->whereDoesntHave('settings')
                        ->orWhereHas('settings', fn ($settings) => $settings->where('show_online', true));
                });

            return;
        }

        $query->where(function ($inner) {
            $inner
                ->whereNull('last_active')
                ->orWhere('last_active', '<', now()->subMinutes(15))
                ->orWhereHas('settings', fn ($settings) => $settings->where('show_online', false));
        });
    }

    private function activitiesFor(User $user): array
    {
        $activities = collect();

        CommunityDiscussion::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(4)
            ->get()
            ->each(fn (CommunityDiscussion $discussion) => $activities->push($this->activity(
                'message',
                'purple',
                "Started {$discussion->title}",
                'Discussions',
                $discussion->created_at
            )));

        CommunityLabResult::query()
            ->where('submitted_by_user_id', $user->id)
            ->latest()
            ->limit(4)
            ->get()
            ->each(fn (CommunityLabResult $result) => $activities->push($this->activity(
                'flask',
                'green',
                "Submitted {$result->compound_name}",
                'Lab Results',
                $result->created_at
            )));

        CommunityVendorReview::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(4)
            ->get()
            ->each(fn (CommunityVendorReview $review) => $activities->push($this->activity(
                'star',
                'amber',
                "Reviewed {$review->title}",
                'Vendor Reviews',
                $review->created_at
            )));

        return $activities
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values()
            ->all();
    }

    private function activity(string $icon, string $tone, string $title, string $category, $occurredAt): array
    {
        return [
            'icon' => $icon,
            'tone' => $tone,
            'title' => $title,
            'subtitle' => null,
            'category' => $category,
            'occurred_at' => $occurredAt?->toIso8601String(),
            'time_ago' => $occurredAt?->diffForHumans(),
        ];
    }
}
