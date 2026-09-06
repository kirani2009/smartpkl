<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required_without:school_id', 'nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'nip' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
