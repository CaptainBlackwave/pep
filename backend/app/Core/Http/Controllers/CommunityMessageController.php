<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityMessageResource;
use App\Core\Http\Resources\CommunityMessageThreadResource;
use App\Core\Models\CommunityMessageThread;
use App\Core\Models\User;
use App\Core\Services\NotificationService;
use App\Core\Services\PushNotificationService;
use App\Core\Services\WebSocketService;
use Illuminate\Http\Request;

class CommunityMessageController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private WebSocketService $websocket,
        private PushNotificationService $push,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $userId = $user->id;

        return CommunityMessageThreadResource::collection(
            CommunityMessageThread::query()
                ->with([
                    'participant.roles',
                    'participant.settings',
                    'messages.sender.roles',
                ])
                ->where('status', 'active')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('participant_user_id', $userId);
                })
                ->orderByDesc('last_message_at')
                ->limit(50)
                ->get()
        );
    }

    public function show(Request $request, string $thread)
    {
        $threadModel = $this->findThread($request, $thread)->load(['participant.roles', 'participant.settings', 'messages.sender.roles']);

        if ($threadModel->unread_count > 0) {
            $threadModel->forceFill(['unread_count' => 0])->save();
        }

        return new CommunityMessageThreadResource($threadModel);
    }

    public function storeThread(Request $request)
    {
        $validated = $request->validate([
            'participant_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'participant_username' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:4000'],
        ]);

        $user = $request->user();
        $participant = $this->findParticipant($validated);

        abort_if(!$participant, 422, 'Choose a valid member to message.');
        abort_if($participant->id === $user->id, 422, 'You cannot message yourself.');
        abort_if($participant->settings?->direct_messages === 'nobody', 403, 'This member is not accepting direct messages.');

        $thread = CommunityMessageThread::query()
            ->where('status', 'active')
            ->where(function ($query) use ($user, $participant) {
                $query->where(['user_id' => $user->id, 'participant_user_id' => $participant->id])
                    ->orWhere(['user_id' => $participant->id, 'participant_user_id' => $user->id]);
            })
            ->first();

        if (!$thread) {
            $thread = CommunityMessageThread::create([
                'user_id' => $user->id,
                'participant_user_id' => $participant->id,
                'status' => 'active',
                'last_message_at' => now(),
            ]);
        }

        if (!empty($validated['body'])) {
            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body' => trim($validated['body']),
                'sent_at' => now(),
            ]);

            $thread->forceFill([
                'last_message_at' => now(),
                'unread_count' => $thread->unread_count + 1,
            ])->save();

            $this->notifications->message($participant, $user->username, $thread->id);
            $this->websocket->toUser($participant, 'notification', [
                'type' => 'message',
                'title' => 'New Message',
                'message' => "New message from {$user->username}",
            ]);
            $this->push->send($participant, "New message from {$user->username}", substr(trim($validated['body']), 0, 120), "/messages/{$thread->id}");
        }

        return (new CommunityMessageThreadResource($thread->load(['participant.roles', 'participant.settings', 'messages.sender.roles'])))
            ->response()
            ->setStatusCode(201);
    }

    public function store(Request $request, string $thread)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $threadModel = $this->findThread($request, $thread);
        $message = $threadModel->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => $validated['body'],
            'sent_at' => now(),
        ]);

        $recipientId = $threadModel->user_id === $request->user()->id
            ? $threadModel->participant_user_id
            : $threadModel->user_id;

        $threadModel->forceFill([
            'last_message_at' => now(),
            'unread_count' => $threadModel->unread_count + 1,
        ])->save();

        $recipient = User::find($recipientId);
        if ($recipient) {
            $this->notifications->message($recipient, $request->user()->username, $threadModel->id);
            $this->websocket->toUser($recipient, 'notification', [
                'type' => 'message',
                'title' => 'New Message',
                'message' => "New message from {$request->user()->username}",
            ]);
            $this->push->send($recipient, "New message from {$request->user()->username}", substr($validated['body'], 0, 120), "/messages/{$threadModel->id}");
        }

        return (new CommunityMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    private function findThread(Request $request, string $value): CommunityMessageThread
    {
        $userId = $request->user()->id;

        return CommunityMessageThread::query()
            ->where('status', 'active')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('participant_user_id', $userId);
            })
            ->whereKey($value)
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function findParticipant(array $validated): ?User
    {
        return User::query()
            ->with('settings')
            ->when(
                !empty($validated['participant_user_id']),
                fn ($query) => $query->whereKey($validated['participant_user_id']),
                fn ($query) => $query->where('username', $validated['participant_username'] ?? '')
            )
            ->first();
    }
}
