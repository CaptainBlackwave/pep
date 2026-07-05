<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityMessageResource;
use App\Core\Http\Resources\CommunityMessageThreadResource;
use App\Core\Models\CommunityMessageThread;
use App\Core\Models\CommunityUserBlock;
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
                    'owner.roles',
                    'owner.settings',
                    'participant.roles',
                    'participant.settings',
                    'messages.sender.roles',
                    'messages.sender.settings',
                ])
                ->where('status', 'active')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('participant_user_id', $userId);
                })
                ->whereColumn('user_id', '!=', 'participant_user_id')
                ->orderByDesc('last_message_at')
                ->limit(50)
                ->get()
        );
    }

    public function show(Request $request, string $thread)
    {
        $threadModel = $this->findThread($request, $thread)->load([
            'owner.roles',
            'owner.settings',
            'participant.roles',
            'participant.settings',
            'messages.sender.roles',
            'messages.sender.settings',
        ]);

        $this->clearUnreadFor($threadModel, $request->user()->id);

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
        abort_if($this->messagingBlockedBetween($user->id, $participant->id), 403, 'Messaging is blocked between these members.');

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
            $senderName = $this->notificationNameFor($user);

            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body' => trim($validated['body']),
                'sent_at' => now(),
            ]);

            $thread->forceFill([
                'last_message_at' => now(),
            ])->save();
            $this->incrementUnreadFor($thread, $participant->id);

            $this->notifications->message($participant, $senderName, $thread->id);
            $this->websocket->toUser($participant, 'notification', [
                'type' => 'message',
                'title' => 'New Message',
                'message' => "New message from {$senderName}",
            ]);
            $this->push->send($participant, "New message from {$senderName}", substr(trim($validated['body']), 0, 120), "/messages?thread={$thread->id}");
        }

        return (new CommunityMessageThreadResource($thread->load([
            'owner.roles',
            'owner.settings',
            'participant.roles',
            'participant.settings',
            'messages.sender.roles',
            'messages.sender.settings',
        ])))
            ->response()
            ->setStatusCode(201);
    }

    public function store(Request $request, string $thread)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $threadModel = $this->findThread($request, $thread);
        $recipientId = $threadModel->user_id === $request->user()->id
            ? $threadModel->participant_user_id
            : $threadModel->user_id;

        $recipient = User::query()->with('settings')->find($recipientId);
        abort_if(!$recipient, 404, 'Recipient not found.');
        abort_if($recipient->settings?->direct_messages === 'nobody', 403, 'This member is not accepting direct messages.');
        abort_if($this->messagingBlockedBetween($request->user()->id, $recipientId), 403, 'Messaging is blocked between these members.');

        $message = $threadModel->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => $validated['body'],
            'sent_at' => now(),
        ]);

        $threadModel->forceFill([
            'last_message_at' => now(),
        ])->save();
        $this->incrementUnreadFor($threadModel, $recipientId);

        $senderName = $this->notificationNameFor($request->user());

        $this->notifications->message($recipient, $senderName, $threadModel->id);
        $this->websocket->toUser($recipient, 'notification', [
            'type' => 'message',
            'title' => 'New Message',
            'message' => "New message from {$senderName}",
        ]);
        $this->push->send($recipient, "New message from {$senderName}", substr($validated['body'], 0, 120), "/messages?thread={$threadModel->id}");

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
            ->whereColumn('user_id', '!=', 'participant_user_id')
            ->whereKey($value)
            ->firstOrFail();
    }

    private function incrementUnreadFor(CommunityMessageThread $thread, int $userId): void
    {
        $ownerUnread = (int) ($thread->owner_unread_count ?? 0);
        $participantUnread = (int) ($thread->participant_unread_count ?? 0);

        if ((int) $thread->user_id === $userId) {
            $ownerUnread++;
        } elseif ((int) $thread->participant_user_id === $userId) {
            $participantUnread++;
        }

        $thread->forceFill([
            'owner_unread_count' => $ownerUnread,
            'participant_unread_count' => $participantUnread,
            'unread_count' => $ownerUnread + $participantUnread,
        ])->save();
    }

    private function clearUnreadFor(CommunityMessageThread $thread, int $userId): void
    {
        $ownerUnread = (int) ($thread->owner_unread_count ?? 0);
        $participantUnread = (int) ($thread->participant_unread_count ?? 0);

        if ((int) $thread->user_id === $userId) {
            $ownerUnread = 0;
        } elseif ((int) $thread->participant_user_id === $userId) {
            $participantUnread = 0;
        }

        $thread->forceFill([
            'owner_unread_count' => $ownerUnread,
            'participant_unread_count' => $participantUnread,
            'unread_count' => $ownerUnread + $participantUnread,
        ])->save();
    }

    private function notificationNameFor(User $user): string
    {
        $freshUser = User::query()
            ->whereKey($user->id)
            ->first(['username', 'name']);

        return $freshUser?->username ?: $freshUser?->name ?: 'member';
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

    private function messagingBlockedBetween(int $senderId, int $recipientId): bool
    {
        return CommunityUserBlock::query()
            ->where(function ($query) use ($senderId, $recipientId) {
                $query->where('user_id', $senderId)
                    ->where('blocked_user_id', $recipientId);
            })
            ->orWhere(function ($query) use ($senderId, $recipientId) {
                $query->where('user_id', $recipientId)
                    ->where('blocked_user_id', $senderId);
            })
            ->exists();
    }
}
