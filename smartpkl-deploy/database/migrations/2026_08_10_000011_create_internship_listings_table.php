<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Lowongan PKL dari perusahaan (docs/ai/INTERNSHIP.json).
 * Status: DRAFT, PUBLISHED, CLOSED, EXPIRED.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            // school_id != null => lowongan khusus siswa sekolah tersebut (harus partnership ACTIVE).
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('position')->nullable();
            $table->text('description');
            $table->unsignedInteger('quota')->default(1);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('location')->nullable();
            $table->decimal('allowance', 12, 2)->nullable();
            $table->text('facilities')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->timestamps();

            $table->index('status');
            $table->index('school_id');
            $table->index('major_id');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_listings');
    }
};
