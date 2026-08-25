<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_listings', function (Blueprint $table) {
            $table->text('required_skills')->nullable()->after('facilities');
        });
    }

    public function down(): void
    {
        Schema::table('internship_listings', function (Blueprint $table) {
            $table->dropColumn('required_skills');
        });
    }
};
