<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\CommunityDiscussionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityCategoryAdminController extends Controller
{
    public function index()
    {
        return CommunityDiscussionCategory::query()
            ->withCount([
                'discussions' => fn ($q) => $q->where('status', 'published'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'sort_order' => $cat->sort_order,
                'discussions_count' => $cat->discussions_count,
                'created_at' => $cat->created_at,
            ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:community_discussion_categories,slug'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $validated['slug'] ??= Str::slug($validated['name']);
        $validated['icon'] ??= 'discussions';
        $validated['color'] ??= 'purple';
        $validated['sort_order'] ??= 0;

        return CommunityDiscussionCategory::create($validated)
            ->fresh()
            ->only(['id', 'name', 'slug', 'description', 'icon', 'color', 'sort_order']);
    }

    public function update(Request $request, CommunityDiscussionCategory $category)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('community_discussion_categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category->update($validated);
        $category->refresh();

        return $category->only(['id', 'name', 'slug', 'description', 'icon', 'color', 'sort_order']);
    }

    public function destroy(CommunityDiscussionCategory $category)
    {
        $category->discussions()->update(['category_id' => null]);
        $category->delete();

        return response()->json(['success' => true]);
    }
}
