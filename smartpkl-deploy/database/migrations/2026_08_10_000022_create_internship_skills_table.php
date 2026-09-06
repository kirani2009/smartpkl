<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Pivot many-to-many lowongan <-> skill.
 * Dibutuhkan oleh INTERNSHIP.json (field "skills") dan SMART_MATCHING.json
 * (input "internship_skills") untuk Smart Matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internship_listings')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['internship_id', 'skill_id']);
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_skills');
    }
};
