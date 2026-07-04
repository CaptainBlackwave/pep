<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityLabResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'compound_name' => $this->compound_name,
            'slug' => $this->slug,
            'compound_type' => $this->compound_type,
            'use_case' => $this->use_case,
            'vendor_name' => $this->vendor_name,
            'batch_code' => $this->batch_code,
            'lab_name' => $this->lab_name,
            'tested_at' => $this->tested_at?->toDateString(),
            'tested_date' => $this->tested_at?->format('M j, Y'),
            'received_at' => $this->received_at?->toDateString(),
            'received_date' => $this->received_at?->format('M j, Y'),
            'report_id' => $this->report_id,
            'sample_type' => $this->sample_type,
            'sample_condition' => $this->sample_condition,
            'purity_percent' => $this->purity_percent !== null ? (float) $this->purity_percent : null,
            'purity' => $this->purity_percent !== null ? rtrim(rtrim(number_format((float) $this->purity_percent, 2), '0'), '.') . '%' : null,
            'water_content_percent' => $this->water_content_percent !== null ? (float) $this->water_content_percent : null,
            'peptide_content_percent' => $this->peptide_content_percent !== null ? (float) $this->peptide_content_percent : null,
            'identity_result' => $this->identity_result,
            'overall_result' => $this->overall_result,
            'coa_filename' => $this->coa_filename,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'views' => $this->views_count,
            'comments' => $this->comments_count,
            'href' => "/lab-results/{$this->slug}",
            'submitted_by' => [
                'id' => $this->submitted_by_user_id,
                'name' => $this->displaySubmitterName(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
