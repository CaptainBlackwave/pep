<?php

namespace App\Plugins\Announcements\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Announcements\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnnouncementsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::with('createdBy');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('message', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        return response()->json(
            $query->orderByDesc('is_sticky')->orderByDesc('created_at')
                  ->paginate((int) $request->get('per_page', 25))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:info,warning,danger,success',
            'target'       => 'required|in:all,level_range,location',
            'min_level'    => 'nullable|integer|min:1',
            'max_level'    => 'nullable|integer|min:1',
            'location_id'  => 'nullable|exists:locations,id',
            'published_at' => 'nullable|date',
            'expires_at'   => 'nullable|date|after:published_at',
            'is_active'    => 'boolean',
            'is_sticky'    => 'boolean',
        ]);

        $validated['created_by'] = $request->user()->id;

        $announcement = Announcement::create($validated);

        return response()->json($announcement->load('createdBy'), 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'message'      => 'sometimes|string',
            'type'         => 'sometimes|in:info,warning,danger,success',
            'target'       => 'sometimes|in:all,level_range,location',
            'min_level'    => 'nullable|integer|min:1',
            'max_level'    => 'nullable|integer|min:1',
            'location_id'  => 'nullable|exists:locations,id',
            'published_at' => 'nullable|date',
            'expires_at'   => 'nullable|date',
            'is_active'    => 'boolean',
            'is_sticky'    => 'boolean',
        ]);

        $announcement->update($validated);

        return response()->json($announcement->load('createdBy'));
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json(null, 204);
    }

    public function types(): JsonResponse
    {
        return response()->json(['info', 'warning', 'danger', 'success']);
    }
}
