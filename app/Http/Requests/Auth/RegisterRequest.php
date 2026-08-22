<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Registrasi terbuka untuk semua pengunjung (siswa, guru, perusahaan).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Admin TIDAK boleh mendaftar sendiri (dibuat via seeder/admin) untuk
            // mencegah privilege escalation.
            'role' => ['required', 'string', 'in:' . implode(',', User::REGISTRABLE_ROLES)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role harus salah satu dari: ' . implode(', ', User::REGISTRABLE_ROLES) . '.',
        ];
    }
}
