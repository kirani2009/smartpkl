<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 5 — Company.
 * API Resource untuk Company.
 */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'verified_at' => $this->verified_at?->toISOString(),
            'profile' => new CompanyProfileResource($this->whenLoaded('profile')),
            'user' => new UserResource($this->whenLoaded('user')),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'partnerships_count' => $this->whenCounted('partnerships'),
            'internship_listings_count' => $this->whenCounted('internshipListings'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
