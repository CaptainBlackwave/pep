<?php

namespace App\Plugins\Community\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\CommunityVendor;
use App\Core\Models\CommunityVendorClaim;
use App\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    private const PROFILE_FIELDS = [
        'cash', 'bank', 'bullets', 'experience', 'level', 'respect',
        'points', 'strength', 'defense', 'speed',
        'health', 'max_health', 'energy', 'max_energy', 'nerve', 'max_nerve',
        'rank_id', 'rank', 'location_id', 'location', 'status', 'jail_until',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['profile', 'roles']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->rank_id) {
            $query->whereHas('profile', fn($q) => $q->where('rank_id', $request->rank_id));
        }

        if ($request->role) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        if ($request->status === 'banned') {
            $query->whereNotNull('banned_until');
        }

        if ($request->status === 'active') {
            $query->whereNull('banned_until');
        }

        $perPage = min((int) ($request->per_page ?? 25), 100);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $now = now();
        $thirtyDaysAgo = (clone $now)->subDays(30);

        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->count(),
            'total_moderators' => User::whereHas('roles', fn($q) => $q->where('name', 'moderator'))->count(),
            'banned_users' => User::whereNotNull('banned_until')->count(),
            'new_users_30d' => User::where('created_at', '>=', $thirtyDaysAgo)->count(),
            'active_users_30d' => User::where('last_active', '>=', $thirtyDaysAgo)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        Log::info('Admin created user', ['admin_id' => $request->user()?->id, 'user_id' => $user->id]);

        return response()->json(['data' => $user->load('roles')], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['profile', 'roles', 'bans' => fn($q) => $q->latest()->limit(5)]);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json(['data' => $user->fresh()->load('roles')]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        Log::info('Admin deleted user', ['admin_id' => $request->user()?->id, 'user_id' => $user->id]);

        return response()->json(['message' => 'User deleted.']);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        $this->authorize('ban', $user);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:87600'],
        ]);

        $until = isset($validated['duration_hours'])
            ? now()->addHours((int) $validated['duration_hours'])
            : now()->addYears(100);

        $user->update([
            'banned_until' => $until,
            'banned_reason' => $validated['reason'] ?? null,
        ]);

        return response()->json(['data' => $user->fresh()]);
    }

    public function unban(Request $request, User $user): JsonResponse
    {
        $this->authorize('ban', $user);

        $user->update([
            'banned_until' => null,
            'banned_reason' => null,
        ]);

        return response()->json(['data' => $user->fresh()]);
    }

    public function updateVendorAccess(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'vendor_access' => ['required', 'boolean'],
        ]);

        $user->forceFill(['vendor_access' => $validated['vendor_access']])->save();

        return response()->json(['data' => $user->fresh()]);
    }

    public function grantVendorProfile(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $vendor = CommunityVendor::create([
            ...$validated,
            'user_id' => $user->id,
            'is_claimed' => true,
            'status' => 'approved',
        ]);

        CommunityVendorClaim::create([
            'vendor_id' => $vendor->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
        ]);

        $user->forceFill(['vendor_access' => true])->save();

        return response()->json(['data' => $vendor->load('user')], 201);
    }
}
