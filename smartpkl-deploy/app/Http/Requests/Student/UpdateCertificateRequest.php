<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 8 — Student Profile.
 * Validasi pembaruan sertifikat.
 */
class UpdateCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'issuer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'issued_at' => ['sometimes', 'nullable', 'date'],
            'file_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
