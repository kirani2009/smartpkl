<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 9 — Application.
 * API Resource untuk Interview.
 */
class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'application' => $this->whenLoaded('application', function () {
                return [
                    'id' => $this->application->id,
                    'status' => $this->application->status,
                    'message' => $this->application->message,
                    'internship' => new InternshipResource($this->application->whenLoaded('internship')),
                ];
            }),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'mode' => $this->mode,
            'location' => $this->location,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
