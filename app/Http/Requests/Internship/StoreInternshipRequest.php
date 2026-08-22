<?php

namespace App\Http\Requests\Internship;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 7 — Internship.
 * Validasi pembuatan lowongan PKL.
 */
class StoreInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'position' => ['nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'exists:schools,id'],
            'major_id' => ['nullable', 'exists:majors,id'],
            'quota' => ['required', 'integer', 'min:1', 'max:100'],
            'period_start' => ['required', 'date', 'after_or_equal:today'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'location' => ['nullable', 'string', 'max:500'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'facilities' => ['nullable', 'string', 'max:1000'],
            'requirements' => ['nullable', 'array', 'max:20'],
            'requirements.*' => ['required', 'string', 'max:500'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['exists:skills,id'],
        ];
    }
}
