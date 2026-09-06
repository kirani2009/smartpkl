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
            'required_major' => ['nullable', 'string', 'max:255'],
            'quota' => ['required', 'integer', 'min:1', 'max:100'],
            'period_start' => ['required', 'date', 'after_or_equal:today'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'location' => ['nullable', 'string', 'max:500'],
            'required_skills' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable', 'array', 'max:20'],
            'requirements.*' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul lowongan wajib diisi.',
            'title.max' => 'Judul lowongan maksimal 255 karakter.',
            'description.required' => 'Deskripsi lowongan wajib diisi.',
            'description.max' => 'Deskripsi lowongan maksimal 5000 karakter.',
            'quota.required' => 'Kuota wajib diisi.',
            'quota.integer' => 'Kuota harus berupa angka.',
            'quota.min' => 'Kuota minimal 1.',
            'quota.max' => 'Kuota maksimal 100.',
            'period_start.required' => 'Tanggal mulai wajib diisi.',
            'period_start.date' => 'Format tanggal mulai tidak valid.',
            'period_start.after_or_equal' => 'Tanggal mulai harus hari ini atau setelahnya.',
            'period_end.required' => 'Tanggal selesai wajib diisi.',
            'period_end.date' => 'Format tanggal selesai tidak valid.',
            'period_end.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
