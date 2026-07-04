<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLabResult extends Model
{
    protected $fillable = [
        'submitted_by_user_id',
        'submitted_by_name',
        'compound_name',
        'slug',
        'compound_type',
        'use_case',
        'vendor_name',
        'batch_code',
        'lab_name',
        'tested_at',
        'received_at',
        'report_id',
        'sample_type',
        'sample_condition',
        'purity_percent',
        'water_content_percent',
        'peptide_content_percent',
        'identity_result',
        'overall_result',
        'coa_filename',
        'notes',
        'status',
        'is_verified',
        'views_count',
        'comments_count',
    ];

    protected function casts(): array
    {
        return [
            'tested_at' => 'date',
            'received_at' => 'date',
            'purity_percent' => 'decimal:2',
            'water_content_percent' => 'decimal:2',
            'peptide_content_percent' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function displaySubmitterName(): string
    {
        return $this->submitted_by_name ?: ($this->submittedBy?->username ?: $this->submittedBy?->name ?: '');
    }
}
