<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'PHP', 'JavaScript', 'TypeScript', 'Python', 'Java', 'Kotlin', 'Swift',
            'React', 'Vue.js', 'Laravel', 'Django', 'Spring Boot', 'Node.js',
            'HTML & CSS', 'Tailwind CSS', 'Bootstrap',
            'MySQL', 'PostgreSQL', 'MongoDB',
            'Git', 'Docker', 'Linux',
            'Figma', 'Adobe Photoshop', 'Adobe Illustrator',
            'Microsoft Office', 'Google Workspace',
            'Public Speaking', 'Team Leadership', 'Problem Solving',
            'Data Analysis', 'UI/UX Design',
        ];

        foreach ($skills as $name) {
            Skill::updateOrCreate(['name' => $name]);
        }
    }
}
