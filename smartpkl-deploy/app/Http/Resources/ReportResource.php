<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk Report (Phase 15 — Reporting).
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'school_id' => $this->school_id,
            'school_name' => $this->whenLoaded('school', fn () => $this->school->name),
            'company_id' => $this->company_id,
            'name' => $this->whenLoaded('company', fn () => $this->company->profile->name ?? $this->company->user->name),
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'data' => $this->data,
            'generated_by' => $this->generated_by,
            'generated_by_name' => $this->whenLoaded('generatedBy', fn () => $this->generatedBy->name),
            'file_path' => $this->file_path,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
