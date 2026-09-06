<?php

namespace App\Http\Requests\Internship;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PHASE 7 — Internship.
 * Validasi pembaruan lowongan PKL.
 */
class UpdateInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'required_major' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quota' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['sometimes', 'date', 'after_or_equal:period_start'],
            'location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'required_skills' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'requirements' => ['sometimes', 'nullable', 'array', 'max:20'],
            'requirements.*' => ['required', 'string', 'max:500'],
        ];
    }
}
