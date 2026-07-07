<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CommunityAnnouncement;
use App\Core\Models\CommunityContentItem;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityLabResult;
use App\Core\Models\CommunityVendor;
use App\Core\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 20);
        $user = $request->user();
        $tier = $this->userTier($user);

        $results = collect();

        // Discussions
        $results = $results->merge(
            CommunityDiscussion::query()
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('excerpt', 'LIKE', "%{$query}%")
                      ->orWhere('tag', 'LIKE', "%{$query}%");
                })
                ->whereHas('category', fn ($q) => $q->where('premium_only', false))
                ->limit($limit)
                ->get()
                ->map(fn ($item) => $this->format($item, 'discussion', "/discussions/{$item->slug}", $item->title, $item->excerpt ?? $item->body))
        );

        // Premium-only discussions
        if ($tier === 'paid') {
            $results = $results->merge(
                CommunityDiscussion::query()
                    ->where('status', 'published')
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                          ->orWhere('excerpt', 'LIKE', "%{$query}%")
                          ->orWhere('tag', 'LIKE', "%{$query}%");
                    })
                    ->whereHas('category', fn ($q) => $q->where('premium_only', true))
                    ->limit($limit)
                    ->get()
                    ->map(fn ($item) => $this->format($item, 'discussion', "/discussions/{$item->slug}", $item->title, $item->excerpt ?? $item->body, true))
            );
        }

        // Vendors (paid only)
        if ($tier === 'paid') {
            $results = $results->merge(
                CommunityVendor::query()
                    ->where('status', 'published')
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn ($item) => $this->format($item, 'vendor', "/vendor-reviews/{$item->slug}", $item->name, $item->description))
            );
        }

        // Lab results (paid only)
        if ($tier === 'paid') {
            $results = $results->merge(
                CommunityLabResult::query()
                    ->where('status', 'published')
                    ->where(function ($q) use ($query) {
                        $q->where('compound_name', 'LIKE', "%{$query}%")
                          ->orWhere('vendor_name', 'LIKE', "%{$query}%")
                          ->orWhere('lab_name', 'LIKE', "%{$query}%")
                          ->orWhere('batch_code', 'LIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn ($item) => $this->format($item, 'lab_result', "/lab-results/{$item->slug}", $item->compound_name, "{$item->vendor_name} — {$item->lab_name}"))
            );
        }

        // Content items (guides, research, FAQ)
        $results = $results->merge(
            CommunityContentItem::query()
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('excerpt', 'LIKE', "%{$query}%")
                      ->orWhere('tag', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $url = match ($item->type) {
                        'guide' => "/guides/{$item->slug}",
                        'faq' => "/guides#faq-{$item->slug}",
                        default => "/research-library/{$item->slug}",
                    };
                    $category = match ($item->type) {
                        'guide', 'faq' => 'guide',
                        default => 'research',
                    };
                    return $this->format($item, $category, $url, $item->title, $item->excerpt ?? $item->body);
                })
        );

        // Announcements
        $results = $results->merge(
            CommunityAnnouncement::query()
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('excerpt', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn ($item) => $this->format($item, 'announcement', "/announcements/{$item->slug}", $item->title, $item->excerpt ?? $item->body))
        );

        // Members (authenticated only)
        if ($user) {
            $results = $results->merge(
                User::query()
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('username', 'LIKE', "%{$query}%")
                          ->orWhere('bio', 'LIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn ($item) => $this->format($item, 'member', "/members/{$item->username}", $item->name ?? $item->username, $item->bio))
            );
        }

        $all = $results->sortByDesc('relevance')->values()->take($limit);

        return response()->json([
            'data' => $all,
            'meta' => [
                'total' => $all->count(),
                'query' => $query,
            ],
        ]);
    }

    private function userTier($user): ?string
    {
        if (!$user) return null;
        return $user->getRawOriginal('tier') ?? $user->tier;
    }

    private function format($item, string $type, string $url, string $title, ?string $text, bool $premium = false): array
    {
        $text = $text ? strip_tags($text) : '';
        return [
            'type' => $type,
            'type_label' => match ($type) {
                'discussion' => 'Discussion',
                'vendor' => 'Vendor',
                'lab_result' => 'Lab Result',
                'guide' => 'Guide',
                'faq' => 'FAQ',
                'research' => 'Research',
                'announcement' => 'Announcement',
                'member' => 'Member',
                default => $type,
            },
            'id' => $item->id,
            'slug' => $item->slug ?? $item->username ?? $item->id,
            'title' => $title,
            'text' => mb_substr($text, 0, 300),
            'url' => $url,
            'premium' => $premium,
            'relevance' => $this->relevanceScore($title, $text),
        ];
    }

    private function relevanceScore(string $title, ?string $text): int
    {
        $query = request()->input('q', '');
        $score = 0;
        $lower = mb_strtolower($query);
        $titleLower = mb_strtolower($title);
        $textLower = mb_strtolower((string) $text);

        if ($titleLower === $lower) $score += 100;
        elseif (str_starts_with($titleLower, $lower)) $score += 50;
        elseif (str_contains($titleLower, $lower)) $score += 25;
        if (str_contains($textLower, $lower)) $score += 10;

        return $score;
    }
}
