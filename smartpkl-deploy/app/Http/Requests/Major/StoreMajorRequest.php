<?php

namespace App\Http\Requests\Major;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 4 — School & Teacher.
 * Menambah jurusan pada sebuah sekolah.
 * Otorisasi (guru sekolah terkait / admin) diperiksa di MajorPolicy.
 */
class StoreMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via MajorPolicy di controller.
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
        ];
    }
}
