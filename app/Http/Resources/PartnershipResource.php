<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 6 — Partnership.
 * API Resource untuk SchoolCompanyPartnership.
 */
class PartnershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'company_id' => $this->company_id,
            'requested_by' => $this->requested_by,
            'status' => $this->status,
            'responded_at' => $this->responded_at?->toISOString(),
            'notes' => $this->notes,
            'school' => new SchoolResource($this->whenLoaded('school')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'requester' => new UserResource($this->whenLoaded('requester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
