<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 7 — Internship.
 * API Resource untuk InternshipListing.
 */
class InternshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'school_id' => $this->school_id,
            'major_id' => $this->major_id,
            'title' => $this->title,
            'position' => $this->position,
            'description' => $this->description,
            'quota' => $this->quota,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'location' => $this->location,
            'required_skills' => $this->required_skills,
            'required_major' => $this->required_major,
            'status' => $this->status,
            'applications_count' => $this->whenCounted('applications'),
            'requirements' => InternshipRequirementResource::collection($this->whenLoaded('requirements')),
            'skills' => $this->whenLoaded('skills'),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'major' => new MajorResource($this->whenLoaded('major')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
