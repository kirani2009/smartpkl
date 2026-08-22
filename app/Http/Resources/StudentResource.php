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
        $completeness = $this->calculateCompleteness();

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
            'profile_completeness' => $completeness['percentage'],
            'missing_fields' => $completeness['missing_fields'],
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

    /**
     * Hitung persentase kelengkapan profil berdasarkan field yang ditentukan.
     *
     * Field yang dihitung:
     * - school_id (selalu ada, tapi tetap dihitung)
     * - major_id
     * - nis
     * - class
     * - gender
     * - phone
     * - address
     * - interests
     * - skills (relasi)
     */
    private function calculateCompleteness(): array
    {
        $requiredFields = [
            'major_id' => 'Jurusan',
            'nis' => 'NIS/NISN',
            'class' => 'Kelas',
            'gender' => 'Jenis Kelamin',
            'phone' => 'Nomor HP',
            'address' => 'Alamat',
            'interests' => 'Minat/Bio',
        ];

        $filled = 0;
        $total = count($requiredFields) + 1; // +1 untuk skills
        $missingFields = [];

        foreach ($requiredFields as $field => $label) {
            if (! empty($this->{$field})) {
                $filled++;
            } else {
                $missingFields[] = $label;
            }
        }

        // Skills dihitung dari relasi
        if ($this->relationLoaded('skills') && $this->skills->count() > 0) {
            $filled++;
        } else {
            $missingFields[] = 'Skill';
        }

        $percentage = $total > 0 ? (int) round(($filled / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
        ];
    }
}
