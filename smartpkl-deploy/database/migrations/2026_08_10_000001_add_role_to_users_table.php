<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 2 — Authentication & Roles.
 *
 * Menambahkan kolom `role` pada tabel users.
 * Role sesuai docs/ai/AUTH.json dan docs/ai/ROLES.json:
 * student, teacher, company, admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'teacher', 'company', 'admin'])
                ->default('student')
                ->after('password');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
