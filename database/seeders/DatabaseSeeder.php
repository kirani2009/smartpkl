<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: schools & majors first, then users, then relationships.
     */
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,
            MajorSeeder::class,
            SkillSeeder::class,
            UserSeeder::class,
            PartnershipSeeder::class,
            InternshipSeeder::class,
            ApplicationSeeder::class,
        ]);
    }
}
