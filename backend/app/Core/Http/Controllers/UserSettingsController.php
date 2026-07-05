<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\CommunityMemberResource;
use App\Core\Http\Resources\UserResource;
use App\Core\Models\CommunityUserBlock;
use App\Core\Models\User;
use App\Core\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserSettingsController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $this->validateProfileAndSettings($request, $user);

        $this->updateProfileFields($user, $validated);
        $this->updateSettingsFields($user, $validated);

        return response()->json([
            'success' => true,
            'user' => new UserResource($user->fresh()->load(['profile', 'roles', 'settings'])),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $this->validateProfileAndSettings($request, $user);

        $this->updateProfileFields($user, $validated);

        return response()->json([
            'success' => true,
            'user' => new UserResource($user->fresh()->load(['profile', 'roles', 'settings'])),
        ]);
    }

    public function notificationSettings(Request $request)
    {
        $settings = $this->settingsFor($request->user());

        return response()->json([
            'emailNotifications' => $settings->email_notifications,
            'pushNotifications' => $settings->push_notifications,
            'soundEnabled' => $settings->sound_enabled,
        ]);
    }

    public function updateNotificationSettings(Request $request)
    {
        $validated = $request->validate([
            'emailNotifications' => ['sometimes', 'boolean'],
            'pushNotifications' => ['sometimes', 'boolean'],
            'soundEnabled' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
            'sound_enabled' => ['sometimes', 'boolean'],
        ]);

        $this->updateSettingsFields($request->user(), $validated);

        return $this->notificationSettings($request);
    }

    public function preferences(Request $request)
    {
        $settings = $this->settingsFor($request->user());

        return response()->json([
            'theme' => $settings->theme,
            'language' => $settings->language,
            'compactMode' => $settings->compact_discussions,
            'showOnlineMembers' => $settings->show_online_members,
            'rememberContentFilters' => $settings->remember_content_filters,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['sometimes', Rule::in(['light', 'dark', 'system'])],
            'language' => ['sometimes', 'string', 'max:20'],
            'compactMode' => ['sometimes', 'boolean'],
            'compact_discussions' => ['sometimes', 'boolean'],
            'showOnlineMembers' => ['sometimes', 'boolean'],
            'show_online_members' => ['sometimes', 'boolean'],
            'rememberContentFilters' => ['sometimes', 'boolean'],
            'remember_content_filters' => ['sometimes', 'boolean'],
        ]);

        $this->updateSettingsFields($request->user(), $validated);

        return $this->preferences($request);
    }

    public function privacy(Request $request)
    {
        return response()->json($this->privacyPayload($this->settingsFor($request->user())));
    }

    public function updatePrivacy(Request $request)
    {
        $validated = $request->validate([
            'show_online' => ['sometimes', 'boolean'],
            'public_profile' => ['sometimes', 'boolean'],
            'profile_visibility' => ['sometimes', Rule::in(['everyone', 'members_only', 'nobody'])],
            'direct_messages' => ['sometimes', Rule::in(['everyone', 'members_only', 'nobody'])],
            'show_read_topics' => ['sometimes', 'boolean'],
            'show_typing' => ['sometimes', 'boolean'],
            'show_recent_activity' => ['sometimes', 'boolean'],
            'personalize_experience' => ['sometimes', 'boolean'],
            'allow_analytics' => ['sometimes', 'boolean'],
        ]);

        $this->updateSettingsFields($request->user(), $validated);

        return $this->privacy($request);
    }

    public function sessions(Request $request)
    {
        $user = $request->user();
        $sessions = collect();

        if (Schema::hasTable('sessions')) {
            $sessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->limit(20)
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'kind' => 'browser',
                    'ipAddress' => $session->ip_address,
                    'userAgent' => $session->user_agent,
                    'lastActivity' => Carbon::createFromTimestamp($session->last_activity)->toISOString(),
                    'isCurrent' => false,
                ]);
        }

        $tokens = $user->tokens()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($token) => [
                'id' => (string) $token->id,
                'kind' => 'token',
                'name' => $token->name,
                'lastActivity' => $token->last_used_at?->toISOString() ?? $token->created_at?->toISOString(),
                'createdAt' => $token->created_at?->toISOString(),
                'expiresAt' => $token->expires_at?->toISOString(),
                'isCurrent' => $request->user()->currentAccessToken()?->id === $token->id,
            ]);

        return response()->json([
            'sessions' => $sessions,
            'tokens' => $tokens,
        ]);
    }

    public function deleteSession(Request $request, string $session)
    {
        $user = $request->user();
        $deleted = 0;

        if (ctype_digit($session)) {
            $deleted = $user->tokens()->whereKey((int) $session)->delete();
        }

        if ($deleted === 0 && Schema::hasTable('sessions')) {
            $deleted = DB::table('sessions')
                ->where('id', $session)
                ->where('user_id', $user->id)
                ->delete();
        }

        return response()->json([
            'success' => $deleted > 0,
        ]);
    }

    public function apiTokens(Request $request)
    {
        return response()->json([
            'data' => $request->user()->tokens()
                ->latest()
                ->get()
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities ?? [],
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'created_at' => $token->created_at?->toISOString(),
                ]),
        ]);
    }

    public function createApiToken(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', 'max:120'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['*'],
            isset($validated['expires_at']) && $validated['expires_at']
                ? Carbon::parse($validated['expires_at'])
                : null
        );

        return response()->json([
            'plain_text_token' => $token->plainTextToken,
            'token' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
                'expires_at' => $token->accessToken->expires_at?->toISOString(),
                'created_at' => $token->accessToken->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function deleteApiToken(Request $request, int $token)
    {
        $deleted = $request->user()->tokens()->whereKey($token)->delete();

        return response()->json([
            'success' => $deleted > 0,
        ]);
    }

    public function blockedUsers(Request $request)
    {
        $blockedIds = CommunityUserBlock::query()
            ->where('user_id', $request->user()->id)
            ->pluck('blocked_user_id');

        $users = User::query()
            ->with(['roles', 'settings'])
            ->withCount([
                'communityDiscussions',
                'communityDiscussionReplies',
                'communityLabResults',
                'communityVendorReviews',
            ])
            ->whereIn('id', $blockedIds)
            ->orderBy('username')
            ->get();

        return CommunityMemberResource::collection($users);
    }

    public function blockUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'required_without:username', 'integer', 'exists:users,id'],
            'username' => ['nullable', 'required_without:user_id', 'string', 'max:255'],
        ]);

        $blockedUser = User::query()
            ->when(
                !empty($validated['user_id']),
                fn ($query) => $query->whereKey((int) $validated['user_id']),
                fn ($query) => $query->where('username', $validated['username'])
                    ->orWhere('name', $validated['username'])
            )
            ->first();

        abort_if(!$blockedUser, 422, 'Choose a valid member to block.');
        abort_if((int) $blockedUser->id === (int) $request->user()->id, 422, 'You cannot block yourself.');

        CommunityUserBlock::firstOrCreate([
            'user_id' => $request->user()->id,
            'blocked_user_id' => (int) $blockedUser->id,
        ]);

        return $this->blockedUsers($request);
    }

    public function unblockUser(Request $request, int $user)
    {
        CommunityUserBlock::query()
            ->where('user_id', $request->user()->id)
            ->where('blocked_user_id', $user)
            ->delete();

        return $this->blockedUsers($request);
    }

    public function avatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $path = $validated['avatar']->store('avatars', 'public');
        $url = $this->publicStorageUrl($request, $path);
        $user = $request->user();
        $user->forceFill([
            'profile_photo_path' => $url,
        ])->save();

        return response()->json([
            'avatar' => $user->profile_photo_path,
            'user' => new UserResource($user->fresh()->load(['profile', 'roles', 'settings'])),
        ]);
    }

    private function publicStorageUrl(Request $request, string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($url, '/');
    }

    private function validateProfileAndSettings(Request $request, User $user): array
    {
        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:20'],
            'website_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'email_notifications' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
            'sound_enabled' => ['sometimes', 'boolean'],
            'show_online' => ['sometimes', 'boolean'],
            'public_profile' => ['sometimes', 'boolean'],
            'profile_visibility' => ['sometimes', Rule::in(['everyone', 'members_only', 'nobody'])],
            'direct_messages' => ['sometimes', Rule::in(['everyone', 'members_only', 'nobody'])],
            'show_read_topics' => ['sometimes', 'boolean'],
            'show_typing' => ['sometimes', 'boolean'],
            'show_recent_activity' => ['sometimes', 'boolean'],
            'personalize_experience' => ['sometimes', 'boolean'],
            'allow_analytics' => ['sometimes', 'boolean'],
            'compact_discussions' => ['sometimes', 'boolean'],
            'show_online_members' => ['sometimes', 'boolean'],
            'remember_content_filters' => ['sometimes', 'boolean'],
            'theme' => ['sometimes', Rule::in(['light', 'dark', 'system'])],
            'language' => ['sometimes', 'string', 'max:20'],
        ]);
    }

    private function updateProfileFields(User $user, array $validated): void
    {
        $fields = ['name', 'username', 'email', 'bio', 'timezone', 'locale', 'website_url'];
        $updates = array_intersect_key($validated, array_flip($fields));

        if (array_key_exists('email', $updates) && $updates['email'] !== $user->email) {
            $updates['email_verified_at'] = null;
        }

        if ($updates !== []) {
            $user->fill($updates)->save();
        }
    }

    private function updateSettingsFields(User $user, array $validated): void
    {
        $settings = $this->settingsFor($user);
        $map = [
            'emailNotifications' => 'email_notifications',
            'pushNotifications' => 'push_notifications',
            'soundEnabled' => 'sound_enabled',
            'compactMode' => 'compact_discussions',
            'showOnlineMembers' => 'show_online_members',
            'rememberContentFilters' => 'remember_content_filters',
        ];

        foreach ($map as $from => $to) {
            if (array_key_exists($from, $validated)) {
                $validated[$to] = $validated[$from];
            }
        }

        $fields = [
            'email_notifications',
            'push_notifications',
            'sound_enabled',
            'show_online',
            'public_profile',
            'profile_visibility',
            'direct_messages',
            'show_read_topics',
            'show_typing',
            'show_recent_activity',
            'personalize_experience',
            'allow_analytics',
            'compact_discussions',
            'show_online_members',
            'remember_content_filters',
            'theme',
            'language',
        ];
        $updates = array_intersect_key($validated, array_flip($fields));

        if ($updates !== []) {
            $settings->fill($updates)->save();
        }
    }

    private function settingsFor(User $user): UserSetting
    {
        return $user->settings()->firstOrCreate([]);
    }

    private function privacyPayload(UserSetting $settings): array
    {
        return [
            'show_online' => $settings->show_online,
            'public_profile' => $settings->public_profile,
            'profile_visibility' => $settings->profile_visibility,
            'direct_messages' => $settings->direct_messages,
            'show_read_topics' => $settings->show_read_topics,
            'show_typing' => $settings->show_typing,
            'show_recent_activity' => $settings->show_recent_activity,
            'personalize_experience' => $settings->personalize_experience,
            'allow_analytics' => $settings->allow_analytics,
        ];
    }
}
