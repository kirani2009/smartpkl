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
            'name' => ['required', 'string', 'max:255'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'school_name' => ['required_without:school_id', 'nullable', 'string', 'max:255'],
            'major_id' => ['nullable', 'exists:majors,id'],
            'major_name' => ['required_without:major_id', 'nullable', 'string', 'max:255'],
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
