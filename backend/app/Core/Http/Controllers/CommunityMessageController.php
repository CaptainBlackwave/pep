<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityMessageResource;
use App\Core\Http\Resources\CommunityMessageThreadResource;
use App\Core\Models\CommunityMessageThread;
use App\Core\Models\User;
use Illuminate\Http\Request;

class CommunityMessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return CommunityMessageThreadResource::collection(
            CommunityMessageThread::query()
                ->with([
                    'participant.roles',
                    'participant.settings',
                    'messages.sender.roles',
                ])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->orderByDesc('last_message_at')
                ->limit(50)
                ->get()
        );
    }

    public function show(Request $request, string $thread)
    {
        $threadModel = $this->findThread($request, $thread)->load(['participant.roles', 'participant.settings', 'messages.sender.roles']);

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

        $thread = CommunityMessageThread::firstOrCreate(
            [
                'user_id' => $user->id,
                'participant_user_id' => $participant->id,
            ],
            [
                'status' => 'active',
                'last_message_at' => now(),
            ]
        );

        if (!empty($validated['body'])) {
            $thread->messages()->create([
                'sender_user_id' => $user->id,
                'body' => trim($validated['body']),
                'sent_at' => now(),
            ]);

            $thread->forceFill(['last_message_at' => now()])->save();
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

        $threadModel->forceFill([
            'last_message_at' => now(),
        ])->save();

        return (new CommunityMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    private function findThread(Request $request, string $value): CommunityMessageThread
    {
        return CommunityMessageThread::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->where(function ($query) use ($value) {
                $query->whereKey($value);
            })
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
