<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 3 — Database Core.
 * Interview opsional untuk lamaran (docs/ai/WORKFLOW.json:
 * "Company may schedule interview"). Satu lamaran bisa punya 0 atau 1 interview aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->enum('mode', ['onsite', 'online'])->default('onsite');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('SCHEDULED');
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
