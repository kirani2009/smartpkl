<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Profil siswa: satu siswa terhubung ke satu akun user (role student), satu sekolah, dan satu jurusan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nis', 30)->nullable();
            $table->string('class', 30)->nullable();
            $table->year('entry_year')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('interests')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index('major_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
