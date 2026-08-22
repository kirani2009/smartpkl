<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 8 — Student Profile.
 * Validasi pembaruan portofolio.
 */
class UpdatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'file_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
