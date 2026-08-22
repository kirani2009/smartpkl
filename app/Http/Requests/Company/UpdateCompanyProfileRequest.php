<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 5 — Company.
 * Validasi pembaruan profil perusahaan.
 */
class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'established_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'employee_count' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
