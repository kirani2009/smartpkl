<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration is no longer needed.
 * school_id was made nullable directly in the original create migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: school_id is already nullable from the original migration.
    }

    public function down(): void
    {
        // No-op.
    }
};
