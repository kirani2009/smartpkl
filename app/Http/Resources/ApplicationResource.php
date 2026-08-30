<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 9 — Application.
 * API Resource untuk Application (lamaran siswa ke lowongan PKL).
 */
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'internship_id' => $this->internship_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'message' => $this->message,
            'rating' => $this->rating,
            'selection_notes' => $this->selection_notes,
            'applied_at' => $this->applied_at?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'internship' => new InternshipResource($this->whenLoaded('internship')),
            'attachments' => ApplicationAttachmentResource::collection($this->whenLoaded('attachments')),
            'status_histories' => ApplicationStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),
            'active_interview' => new InterviewResource($this->whenLoaded('activeInterview')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
