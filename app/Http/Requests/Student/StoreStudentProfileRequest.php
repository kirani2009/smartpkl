<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 8 — Student Profile.
 * Validasi pembuatan profil siswa.
 */
class StoreStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'exists:schools,id'],
            'major_id' => ['nullable', 'exists:majors,id'],
            'nis' => ['nullable', 'string', 'max:30'],
            'class' => ['nullable', 'string', 'max:30'],
            'entry_year' => ['nullable', 'integer', 'min:2000', 'max:' . date('Y')],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'interests' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
