<?php

namespace App\Http\Requests\Major;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 4 — School & Teacher.
 * Memperbarui jurusan.
 */
class UpdateMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via MajorPolicy di controller.
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
