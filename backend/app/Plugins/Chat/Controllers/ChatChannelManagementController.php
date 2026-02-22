<?php

namespace App\Plugins\Chat\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Plugins\Chat\Models\ChatChannel;
use Illuminate\Http\Request;

class ChatChannelManagementController extends Controller
{
    public function index()
    {
        $channels = ChatChannel::withCount('messages', 'members')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($channels);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:chat_channels,name',
            'description' => 'nullable|string|max:500',
            'type'        => 'required|string|in:public,private,game',
            'max_members' => 'nullable|integer|min:2|max:10000',
            'is_active'   => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active']  = $validated['is_active'] ?? true;

        $channel = ChatChannel::create($validated);
        return response()->json($channel->loadCount('messages', 'members'), 201);
    }

    public function show(string $id)
    {
        $channel = ChatChannel::withCount('messages', 'members')->findOrFail($id);
        return response()->json($channel);
    }

    public function update(Request $request, string $id)
    {
        $channel = ChatChannel::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:chat_channels,name,' . $id,
            'description' => 'nullable|string|max:500',
            'type'        => 'sometimes|string|in:public,private,game',
            'max_members' => 'nullable|integer|min:2|max:10000',
            'is_active'   => 'boolean',
        ]);

        $channel->update($validated);
        return response()->json($channel->fresh()->loadCount('messages', 'members'));
    }

    public function destroy(string $id)
    {
        $channel = ChatChannel::findOrFail($id);
        $channel->messages()->delete();
        $channel->delete();
        return response()->json(null, 204);
    }
}
