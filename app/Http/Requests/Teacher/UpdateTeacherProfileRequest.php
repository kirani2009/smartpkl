<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_id' => ['sometimes', 'nullable', 'integer', 'exists:schools,id'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:30'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
