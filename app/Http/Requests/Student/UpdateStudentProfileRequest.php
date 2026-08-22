<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 8 — Student Profile.
 * Validasi pembaruan profil siswa.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['sometimes', 'exists:schools,id'],
            'major_id' => ['sometimes', 'nullable', 'exists:majors,id'],
            'nis' => ['sometimes', 'nullable', 'string', 'max:30'],
            'class' => ['sometimes', 'nullable', 'string', 'max:30'],
            'entry_year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:' . date('Y')],
            'gender' => ['sometimes', 'nullable', 'in:male,female'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'interests' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
