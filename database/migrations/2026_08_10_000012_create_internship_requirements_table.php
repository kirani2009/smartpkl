<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Persyaratan lowongan PKL (bisa lebih dari satu per lowongan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internship_listings')->cascadeOnDelete();
            $table->string('description');
            $table->timestamps();

            $table->index('internship_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_requirements');
    }
};
