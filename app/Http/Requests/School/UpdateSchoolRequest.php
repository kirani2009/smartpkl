<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PHASE 4 — School & Teacher.
 * Memperbarui sekolah (hanya admin).
 */
class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via middleware role:admin di route.
    }

    public function rules(): array
    {
        $schoolId = $this->route('school')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'npsn' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($schoolId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
