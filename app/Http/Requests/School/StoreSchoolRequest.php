<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 4 — School & Teacher.
 * Membuat sekolah (hanya admin, ROLES.json: admin "manage schools").
 */
class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi via middleware role:admin di route.
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20', 'unique:schools,npsn'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
