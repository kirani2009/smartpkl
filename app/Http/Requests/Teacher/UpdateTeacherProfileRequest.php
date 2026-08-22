<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 4 — School & Teacher.
 * Guru memperbarui profilnya sendiri.
 */
class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via middleware role:teacher di route.
    }

    public function rules(): array
    {
        return [
            'school_id' => ['sometimes', 'integer', 'exists:schools,id'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:30'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
