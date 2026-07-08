<?php

namespace App\Plugins\Vendors\Controllers\Admin;

use App\Core\Models\AdminNotification;
use App\Core\Models\VendorAccessRequest;
use App\Core\Services\AdminNotificationService;
use App\Core\Services\NotificationService;
use App\Core\Services\WebSocketService;
use Illuminate\Http\Request;

class VendorAccessRequestController
{
    public function __construct(
        private AdminNotificationService $adminNotifications,
        private NotificationService $notifications,
        private WebSocketService $websocket,
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

        $this->notifications->create(
            $user,
            'vendor_request',
            'Vendor Access Request Submitted',
            'Your vendor access request has been submitted and is pending admin review.',
            ['request_id' => $vendorAccessRequest->id],
            '🏪',
            null
        );

        $this->websocket->toUser($user, 'notification', [
            'type' => 'vendor_request',
            'title' => 'Vendor Access Request',
            'message' => 'Your request has been submitted for review.',
        ]);

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

        $user = $vendorAccessRequest->user;

        if ($validated['status'] === 'approved') {
            $user->update([
                'is_approved_vendor' => true,
            ]);

            $this->notifications->create(
                $user,
                'vendor_request',
                'Vendor Access Approved',
                'Your vendor access request has been approved. You can now manage your vendor listing.',
                ['request_id' => $vendorAccessRequest->id],
                '✅',
                '/vendors/my'
            );
        } else {
            $this->notifications->create(
                $user,
                'vendor_request',
                'Vendor Access Denied',
                'Your vendor access request has been denied.' . ($validated['admin_note'] ? " Reason: {$validated['admin_note']}" : ''),
                ['request_id' => $vendorAccessRequest->id],
                '❌',
                null
            );
        }

        $this->websocket->toUser($user, 'notification', [
            'type' => 'vendor_request',
            'title' => 'Vendor Access ' . ucfirst($validated['status']),
            'message' => $validated['status'] === 'approved'
                ? 'Your vendor access has been approved!'
                : 'Your vendor access request has been denied.',
        ]);

        return response()->json([
            'message' => 'Vendor access request ' . $validated['status'] . '.',
            'data' => $vendorAccessRequest->load('user'),
        ]);
    }
}
