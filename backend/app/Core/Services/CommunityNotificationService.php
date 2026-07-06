<?php

namespace App\Core\Services;

use App\Core\Models\CommunityAnnouncement;
use App\Core\Models\CommunityContentItem;
use App\Core\Models\CommunityLabResult;
use App\Core\Models\CommunityNotification;
use App\Core\Models\CommunityVendorReview;
use Illuminate\Support\Str;

class CommunityNotificationService
{
    public function syncAnnouncement(CommunityAnnouncement $announcement): ?CommunityNotification
    {
        $announcement->loadMissing('author');

        if ($announcement->status !== 'published') {
            return $this->hideForSource('announcement', $announcement->id);
        }

        return $this->upsertForSource('announcement', $announcement->id, [
            'user_id' => $announcement->user_id,
            'author_name' => $announcement->user_id ? null : $announcement->displayAuthorName(),
            'title' => $announcement->title,
            'category' => 'Announcements',
            'category_slug' => 'announcements',
            'icon' => $announcement->icon ?: 'megaphone',
            'tone' => $announcement->tone ?: 'purple',
            'excerpt' => $announcement->excerpt,
            'body' => $announcement->body,
            'source_url' => "/announcements/{$announcement->slug}",
            'is_pinned' => $announcement->is_pinned,
            'published_at' => $announcement->published_at ?: now(),
        ]);
    }

    public function syncLabResult(CommunityLabResult $labResult): ?CommunityNotification
    {
        $labResult->loadMissing('submittedBy');

        if ($labResult->status !== 'published') {
            return $this->hideForSource('lab_result', $labResult->id);
        }

        $summary = trim(implode(' ', array_filter([
            $labResult->compound_name,
            $labResult->vendor_name ? "from {$labResult->vendor_name}" : null,
            $labResult->lab_name ? "tested by {$labResult->lab_name}" : null,
            $labResult->purity_percent !== null ? "with {$labResult->purity_percent}% purity." : null,
        ])));

        return $this->upsertForSource('lab_result', $labResult->id, [
            'user_id' => $labResult->submitted_by_user_id,
            'author_name' => $labResult->submitted_by_user_id ? null : $labResult->displaySubmitterName(),
            'title' => $labResult->compound_name,
            'category' => 'Lab Results',
            'category_slug' => 'lab-results',
            'icon' => 'flask',
            'tone' => 'blue',
            'excerpt' => $summary,
            'body' => $labResult->notes,
            'source_url' => "/lab-results/{$labResult->slug}",
            'published_at' => $labResult->updated_at ?: now(),
        ]);
    }

    public function syncContent(CommunityContentItem $content): ?CommunityNotification
    {
        $content->loadMissing('author');

        if ($content->status !== 'published') {
            return $this->hideForSource("content_{$content->type}", $content->id);
        }

        $category = match ($content->type) {
            'guide', 'faq' => 'Guides & FAQ',
            default => 'Research Library',
        };

        $sourceUrl = match ($content->type) {
            'guide' => "/guides/{$content->slug}",
            'faq' => "/guides#faq-{$content->slug}",
            default => "/research-library/{$content->slug}",
        };

        return $this->upsertForSource("content_{$content->type}", $content->id, [
            'user_id' => $content->user_id,
            'author_name' => $content->user_id ? null : $content->displayAuthorName(),
            'title' => $content->title,
            'category' => $category,
            'category_slug' => Str::slug($category),
            'icon' => $content->icon ?: 'document',
            'tone' => $content->type === 'guide' ? 'purple' : 'green',
            'excerpt' => $content->excerpt,
            'body' => $content->body,
            'source_url' => $sourceUrl,
            'published_at' => $content->published_at ?: now(),
        ]);
    }

    public function syncVendorReview(CommunityVendorReview $review): ?CommunityNotification
    {
        $review->loadMissing(['user', 'vendor']);

        if ($review->status !== 'published') {
            return $this->hideForSource('vendor_review', $review->id);
        }

        $vendorName = $review->vendor?->name;

        return $this->upsertForSource('vendor_review', $review->id, [
            'user_id' => $review->user_id,
            'author_name' => $review->user_id ? null : $review->displayAuthorName(),
            'title' => $review->title,
            'category' => 'Vendor Reviews',
            'category_slug' => 'vendor-reviews',
            'icon' => 'star',
            'tone' => 'purple',
            'excerpt' => \Illuminate\Support\Str::limit($vendorName ? "{$vendorName}: {$review->body}" : $review->body, 400),
            'body' => $review->body,
            'source_url' => $review->vendor ? "/vendor-reviews/{$review->vendor->slug}" : '/vendor-reviews',
            'published_at' => $review->reviewed_at ?: $review->updated_at ?: now(),
        ]);
    }

    public function hideForSource(string $sourceType, int $sourceId): ?CommunityNotification
    {
        $notification = CommunityNotification::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($notification) {
            $notification->forceFill(['status' => 'hidden'])->save();
        }

        return $notification;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function upsertForSource(string $sourceType, int $sourceId, array $attributes): CommunityNotification
    {
        $title = (string) $attributes['title'];
        $categorySlug = (string) $attributes['category_slug'];
        $slug = $this->uniqueSlug("{$categorySlug}-{$title}", $sourceType, $sourceId);

        return CommunityNotification::updateOrCreate(
            [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                ...$attributes,
                'slug' => $slug,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'published',
            ]
        );
    }

    private function uniqueSlug(string $value, string $sourceType, int $sourceId): string
    {
        $base = Str::slug($value) ?: 'notification';
        $slug = $base;
        $suffix = 2;

        while (CommunityNotification::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($sourceType, $sourceId) {
                $query
                    ->where('source_type', '!=', $sourceType)
                    ->orWhere('source_id', '!=', $sourceId)
                    ->orWhereNull('source_type')
                    ->orWhereNull('source_id');
            })
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
