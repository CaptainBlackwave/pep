<?php

namespace App\Plugins\Community\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityMemberResource;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionReply;
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
            'discussion' => ['nullable', 'string', 'max:180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $query = $this->memberQuery($request->user());

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

        if (!empty($validated['discussion'])) {
            $discussion = CommunityDiscussion::query()
                ->where('status', 'published')
                ->where(function ($inner) use ($validated) {
                    $inner->where('slug', $validated['discussion']);

                    if (ctype_digit($validated['discussion'])) {
                        $inner->orWhere('id', (int) $validated['discussion']);
                    }
                })
                ->first();

            if ($discussion) {
                $participantIds = collect([$discussion->user_id])
                    ->merge(
                        CommunityDiscussionReply::query()
                            ->where('discussion_id', $discussion->id)
                            ->pluck('user_id')
                    )
                    ->filter()
                    ->unique()
                    ->values();

                $query->whereIn('id', $participantIds);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        $limit = (int) ($validated['limit'] ?? 40);
        $statsQuery = clone $query;
        $onlineQuery = clone $query;
        $this->applyOnlineFilter($onlineQuery, true);
        $topContributors = $this->topContributors();
        $onlineMembers = $this->onlineMembers();

        return CommunityMemberResource::collection(
            $query->orderByDesc('last_active')->latest()->paginate($limit)->withQueryString()
        )->additional([
            'meta' => [
                'stats' => [
                    'total' => (clone $statsQuery)->count(),
                    'online' => $onlineQuery->count(),
                ],
                'top_contributors' => CommunityMemberResource::collection($topContributors)->resolve($request),
                'online_members' => CommunityMemberResource::collection($onlineMembers)->resolve($request),
            ],
        ]);
    }

    public function show(Request $request, string $member)
    {
        $user = $this->memberQuery($request->user())
            ->where(function ($query) use ($member) {
                $query->where('username', $member)->orWhere('name', $member);

                if (ctype_digit($member)) {
                    $query->orWhere('id', (int) $member);
                }
            })
            ->firstOrFail();

        $user->setAttribute('community_activities', $this->activitiesFor($user));
        $user->setAttribute('community_tab_data', $this->tabDataFor($user));

        return new CommunityMemberResource($user);
    }

    private function memberQuery(?User $viewer = null)
    {
        $query = User::query()
            ->with(['roles', 'settings'])
            ->withCount([
                'communityDiscussions',
                'communityDiscussionReplies',
                'communityLabResults',
                'communityVendorReviews',
            ]);

        if (!$viewer?->hasRole('admin')) {
            $query->where(function ($q) {
                $q
                    ->whereDoesntHave('settings')
                    ->orWhereHas('settings', fn ($settings) => $settings
                        ->where('public_profile', true)
                        ->where('profile_visibility', '!=', 'nobody'));
            });
        }

        return $query;
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

    private function tabDataFor(User $user): array
    {
        $discussions = CommunityDiscussion::query()
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CommunityDiscussion $discussion) => [
                'type' => 'discussion',
                'title' => $discussion->title,
                'text' => $discussion->excerpt,
                'href' => "/discussions/{$discussion->slug}",
                'time' => $discussion->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();

        $replies = CommunityDiscussionReply::query()
            ->with('discussion')
            ->where('user_id', $user->id)
            ->whereHas('discussion', fn ($query) => $query->where('status', 'published'))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CommunityDiscussionReply $reply) => [
                'type' => 'reply',
                'title' => $reply->discussion?->title ?? 'Discussion reply',
                'text' => $reply->body,
                'href' => $reply->discussion ? "/discussions/{$reply->discussion->slug}" : '/discussions',
                'time' => $reply->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();

        $reviews = CommunityVendorReview::query()
            ->with('vendor')
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CommunityVendorReview $review) => [
                'type' => 'review',
                'title' => $review->title,
                'text' => $review->body,
                'href' => $review->vendor ? "/vendor-reviews/{$review->vendor->slug}" : '/vendor-reviews',
                'time' => $review->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();

        $labResults = CommunityLabResult::query()
            ->where('submitted_by_user_id', $user->id)
            ->where('status', 'published')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CommunityLabResult $result) => [
                'type' => 'lab',
                'title' => $result->compound_name,
                'text' => trim("{$result->vendor_name} {$result->batch_code}"),
                'href' => "/lab-results/{$result->slug}",
                'time' => $result->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();

        return [
            'overview' => collect($discussions)
                ->merge($replies)
                ->merge($reviews)
                ->merge($labResults)
                ->take(12)
                ->values()
                ->all(),
            'activity' => $this->activitiesFor($user),
            'posts' => collect($discussions)->merge($replies)->values()->all(),
            'reviews' => $reviews,
            'guides' => [],
            'badges' => $user->roles->pluck('name')->map(fn (string $role) => ucfirst($role))->values()->all(),
        ];
    }

    private function topContributors()
    {
        return $this->memberQuery()
            ->get()
            ->sortByDesc(fn (User $user) => (int) ($user->community_discussions_count ?? 0)
                + (int) ($user->community_discussion_replies_count ?? 0)
                + (int) ($user->community_lab_results_count ?? 0)
                + (int) ($user->community_vendor_reviews_count ?? 0))
            ->take(5)
            ->values();
    }

    private function onlineMembers()
    {
        $query = $this->memberQuery();
        $this->applyOnlineFilter($query, true);

        return $query->orderByDesc('last_active')->limit(80)->get();
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
