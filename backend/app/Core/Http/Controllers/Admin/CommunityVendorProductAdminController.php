<?php

namespace App\Core\Http\Controllers\Admin;

use App\Core\Http\Controllers\Controller;
use App\Core\Http\Resources\CommunityVendorProductResource;
use App\Core\Models\CommunityVendor;
use App\Core\Models\CommunityVendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityVendorProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:community_vendors,id'],
            'status' => ['nullable', Rule::in(['all', 'published', 'hidden'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CommunityVendorProduct::query()->with('vendor');

        if (!empty($validated['vendor_id'])) {
            $query->where('vendor_id', $validated['vendor_id']);
        }

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        $limit = (int) ($validated['limit'] ?? 100);

        return CommunityVendorProductResource::collection(
            $query->orderByDesc('updated_at')->limit($limit)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:community_vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'strength' => ['nullable', 'string', 'max:80'],
            'package_size' => ['nullable', 'string', 'max:80'],
            'purity_label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:50000'],
            'variants' => ['nullable', 'array'],
            'variants.*.label' => ['required', 'string', 'max:80'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
        ]);

        $validated['status'] ??= 'published';
        $validated['availability'] ??= 'in_stock';
        $validated['slug'] = $this->uniqueSlug($validated['vendor_id'], $validated['slug'] ?? $validated['name']);

        $product = CommunityVendorProduct::create($validated);

        return (new CommunityVendorProductResource($product->load('vendor')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, CommunityVendorProduct $product)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'strength' => ['nullable', 'string', 'max:80'],
            'package_size' => ['nullable', 'string', 'max:80'],
            'purity_label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:50000'],
            'variants' => ['nullable', 'array'],
            'variants.*.label' => ['required', 'string', 'max:80'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'availability' => ['nullable', Rule::in(['in_stock', 'limited', 'out_of_stock'])],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['published', 'hidden'])],
        ]);

        $product->fill($validated)->save();

        // Regenerate slug if name changed and no custom slug provided
        if (!empty($validated['name']) && empty($validated['slug'])) {
            $product->slug = $this->uniqueSlug($product->vendor_id, $validated['name'], $product->id);
            $product->save();
        }

        return new CommunityVendorProductResource($product->fresh()->load('vendor'));
    }

    public function destroy(CommunityVendorProduct $product)
    {
        $product->forceFill(['status' => 'hidden'])->save();

        return response()->json([
            'success' => true,
            'message' => 'Product hidden.',
        ]);
    }
}
