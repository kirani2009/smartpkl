<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Pendaftaran siswa ke lowongan (docs/ai/APPLICATION.json).
 * Status: PENDING, REVIEWED, INTERVIEW, ACCEPTED, REJECTED.
 * Unique (internship_id, student_id) => satu siswa tidak bisa apply dua kali ke lowongan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internship_listings')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->text('message')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->unique(['internship_id', 'student_id']);
            $table->index('student_id');
            $table->index('status');
            $table->index(['internship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
