<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 10 — Selection.
 * Menambahkan kolom rating dan selection_notes pada tabel applications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('message');
            $table->text('selection_notes')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['rating', 'selection_notes']);
        });
    }
};
