<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CommunityVendorDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'title' => $this->title,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'category' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'url' => Storage::disk('public')->url($this->file_path),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
