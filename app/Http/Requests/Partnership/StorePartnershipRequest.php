<?php

namespace App\Http\Requests\Partnership;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 6 — Partnership.
 * Validasi pengiriman partnership request dari guru ke perusahaan.
 */
class StorePartnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
