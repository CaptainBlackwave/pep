<?php

namespace App\Plugins\Vendors\Controllers;

use App\Core\Models\AdminNotification;
use App\Core\Models\VendorAccessRequest;
use App\Core\Services\AdminNotificationService;
use Illuminate\Http\Request;

class VendorAccessRequestController
{
    public function __construct(
        private AdminNotificationService $adminNotifications,
    ) {
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $existing = VendorAccessRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You already have a pending vendor access request.',
            ], 409);
        }

        $vendorAccessRequest = VendorAccessRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->adminNotifications->notifyAll(
            AdminNotification::TYPE_TASK,
            'Vendor Access Request',
            "{$user->name} requested vendor access.",
            ['user_id' => $user->id, 'request_id' => $vendorAccessRequest->id],
            '🏪',
            '/vendor-access-requests',
            AdminNotification::PRIORITY_NORMAL
        );

        return response()->json([
            'message' => 'Vendor access request submitted.',
            'data' => $vendorAccessRequest,
        ], 201);
    }

    public function index(Request $request)
    {
        $requests = VendorAccessRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests,
        ]);
    }

    public function update(Request $request, VendorAccessRequest $vendorAccessRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,denied',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        $vendorAccessRequest->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        if ($validated['status'] === 'approved') {
            $vendorAccessRequest->user()->update([
                'is_approved_vendor' => true,
            ]);
        }

        return response()->json([
            'message' => 'Vendor access request ' . $validated['status'] . '.',
            'data' => $vendorAccessRequest->load('user'),
        ]);
    }
}
