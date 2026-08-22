<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 4 — School & Teacher.
 * Guru membuat/melengkapi profilnya sendiri (memilih sekolah tempat mengajar).
 */
class StoreTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via middleware role:teacher di route.
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'nip' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
