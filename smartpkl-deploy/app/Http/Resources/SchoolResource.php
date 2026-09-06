<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 4 — School & Teacher.
 */
class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'npsn' => $this->npsn,
            'address' => $this->address,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
            'description' => $this->description,
            'majors_count' => $this->whenCounted('majors'),
            'teachers_count' => $this->whenCounted('teachers'),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
