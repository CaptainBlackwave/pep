<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityDiscussionReport;
use App\Core\Models\CommunityDiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityModerationController extends Controller
{
    public function reports(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'reviewed', 'dismissed', 'all'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityDiscussionReport::query()->with('user')->latest();

        $statusFilter = $validated['status'] ?? 'open';
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $reports = $query->limit($validated['limit'] ?? 50)->get()->map(function ($report) {
            $target = null;
            if ($report->target_type === 'discussion') {
                $target = CommunityDiscussion::with('user')->find($report->target_id);
            } elseif ($report->target_type === 'reply') {
                $target = CommunityDiscussionReply::with('user')->find($report->target_id);
            }

            return [
                'id' => $report->id,
                'target_type' => $report->target_type,
                'target_id' => $report->target_id,
                'reason' => $report->reason,
                'details' => $report->details,
                'status' => $report->status,
                'reporter' => $report->user ? [
                    'id' => $report->user->id,
                    'name' => $report->user->name,
                    'username' => $report->user->username,
                ] : null,
                'target' => $target ? [
                    'id' => $target->id,
                    'title' => $target->title ?? ($target->body ? substr((string)$target->body, 0, 100) : ''),
                    'author' => $target->user?->name ?? 'unknown',
                    'url' => $target->slug ?? '#',
                ] : null,
                'created_at' => $report->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $reports,
            'meta' => [
                'stats' => [
                    'open' => CommunityDiscussionReport::where('status', 'open')->count(),
                    'reviewed' => CommunityDiscussionReport::where('status', 'reviewed')->count(),
                    'dismissed' => CommunityDiscussionReport::where('status', 'dismissed')->count(),
                ],
            ],
        ]);
    }

    public function updateReport(Request $request, CommunityDiscussionReport $report)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['reviewed', 'dismissed'])],
        ]);

        $report->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Report ' . $validated['status'] . '.']);
    }
}
