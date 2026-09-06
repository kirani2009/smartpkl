<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Lowongan yang disimpan siswa (bookmark) sesuai ROLES.json "save internship".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained('internship_listings')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'internship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_internships');
    }
};
