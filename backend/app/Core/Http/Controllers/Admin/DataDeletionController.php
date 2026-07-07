<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Models\DataDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DataDeletionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DataDeletionRequest::with('processor')->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where('email', 'like', "%{$request->search}%");
        }

        return response()->json($query->paginate(25));
    }

    public function show(DataDeletionRequest $request): JsonResponse
    {
        $request->load('processor');
        return response()->json($request);
    }

    public function update(Request $request, DataDeletionRequest $deletionRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $deletionRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json($deletionRequest->fresh('processor'));
    }
}
