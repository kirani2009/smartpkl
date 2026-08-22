<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 9 — Application.
 * API Resource untuk ApplicationStatusHistory.
 */
class ApplicationStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'status' => $this->status,
            'changed_by' => $this->changed_by,
            'note' => $this->note,
            'changed_by_user' => new UserResource($this->whenLoaded('changedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
