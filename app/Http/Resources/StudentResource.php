<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 8 — Student Profile.
 * API Resource untuk Student.
 */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'school_id' => $this->school_id,
            'major_id' => $this->major_id,
            'nis' => $this->nis,
            'class' => $this->class,
            'entry_year' => $this->entry_year,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'phone' => $this->phone,
            'address' => $this->address,
            'interests' => $this->interests,
            'school' => new SchoolResource($this->whenLoaded('school')),
            'major' => new MajorResource($this->whenLoaded('major')),
            'user' => new UserResource($this->whenLoaded('user')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'portfolios' => PortfolioResource::collection($this->whenLoaded('portfolios')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'certificates' => CertificateResource::collection($this->whenLoaded('certificates')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
