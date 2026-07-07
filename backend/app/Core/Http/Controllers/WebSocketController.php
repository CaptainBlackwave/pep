<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\GuestSession;
use App\Core\Services\WebSocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebSocketController extends Controller
{
    public function __construct(
        protected WebSocketService $wsService
    ) {}

    /**
     * Authorize a private/presence channel subscription
     */
    public function authorizeChannel(Request $request): JsonResponse
    {
        $user = $request->user();
        $channel = $request->input('channel_name');

        if (!$this->wsService->authorizeChannel($user, $channel)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // For presence channels, include user info
        if (str_starts_with($channel, 'presence-')) {
            return response()->json([
                'user_id' => $user->id,
                'user_info' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'avatar' => $user->avatar ?? null,
                ],
            ]);
        }

        return response()->json(['auth' => true]);
    }

    /**
     * Long-polling fallback for clients without WebSocket support
     */
    public function poll(Request $request): JsonResponse
    {
        $request->validate([
            'channels' => 'required|array',
            'channels.*' => 'string',
            'since' => 'nullable|string',
        ]);

        $user = $request->user();
        $messages = [];

        foreach ($request->channels as $channel) {
            // Check authorization
            if (!$this->wsService->authorizeChannel($user, $channel)) {
                continue;
            }

            $channelMessages = $this->wsService->getMessages($channel, $request->since);
            $messages = array_merge($messages, $channelMessages);
        }

        // Sort by timestamp
        usort($messages, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return response()->json([
            'messages' => $messages,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get online users count
     */
    public function onlineCount(): JsonResponse
    {
        $counts = $this->wsService->getOnlineCounts();

        return response()->json([
            'count' => $counts['members'],
            'members' => $counts['members'],
            'guests' => $counts['guests'],
            'visits_today' => $this->wsService->getVisitsToday(),
            'guest_activity' => $this->wsService->getGuestActivity(),
        ]);
    }

    /**
     * Heartbeat to maintain online status
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => ['nullable', 'string', 'max:80'],
            'path' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user('sanctum');
        $currentPath = $this->normaliseActivityPath($validated['path'] ?? '/');
        $currentLabel = $this->normaliseActivityLabel($validated['label'] ?? 'Community');

        if ($user) {
            $this->wsService->setOnline($user);
            $user->update(['last_active' => now()]);
        }

        $guestId = $validated['guest_id'] ?? null;
        if ($guestId) {
            GuestSession::updateOrCreate(
                ['guest_id' => $guestId],
                [
                    'current_path' => $currentPath,
                    'current_label' => $currentLabel,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                    'last_active' => now(),
                ]
            );
            $this->wsService->broadcastOnlineCounts();
        }

        $counts = $this->wsService->getOnlineCounts();

        return response()->json([
            'status' => 'ok',
            'members' => $counts['members'],
            'guests' => $counts['guests'],
            'visits_today' => $this->wsService->getVisitsToday(),
            'guest_activity' => $this->wsService->getGuestActivity(),
        ]);
    }

    private function normaliseActivityPath(string $path): string
    {
        $path = Str::of($path)
            ->before('?')
            ->trim()
            ->limit(255, '')
            ->toString();

        return $path !== '' && str_starts_with($path, '/') ? $path : '/';
    }

    private function normaliseActivityLabel(string $label): string
    {
        $label = Str::of($label)
            ->squish()
            ->limit(120, '')
            ->toString();

        return $label !== '' ? $label : 'Community';
    }

    /**
     * Get presence channel members
     */
    public function presenceMembers(Request $request, string $channel): JsonResponse
    {
        $user = $request->user();

        if (!$this->wsService->authorizeChannel($user, $channel)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'members' => array_values($this->wsService->getPresenceMembers($channel)),
        ]);
    }
}
