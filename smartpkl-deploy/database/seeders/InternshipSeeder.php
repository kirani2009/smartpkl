<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InternshipListing;
use App\Models\InternshipRequirement;
use App\Models\Major;
use App\Models\SchoolCompanyPartnership;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with('user')->get();
        $majors = Major::all();
        $skills = Skill::all();

        if ($companies->isEmpty()) {
            return;
        }

        $techCorp = $companies->firstWhere('user.email', 'hrd@techcorp.id');
        $digitalStudio = $companies->firstWhere('user.email', 'info@digitalstudio.id');
        $multiNet = $companies->firstWhere('user.email', 'recruit@multinet.id');

        // Get accepted partnership schools
        $acceptedPartnerships = SchoolCompanyPartnership::where('status', 'ACCEPTED')->get();

        $rplMajor = $majors->firstWhere('code', 'RPL');
        $tkjMajor = $majors->firstWhere('code', 'TKJ');
        $mmMajor = $majors->firstWhere('code', 'MM');

        $internships = [
            // TechCorp — Lowongan umum (school_id null)
            [
                'company' => $techCorp,
                'title' => 'Frontend Developer Intern',
                'position' => 'Frontend Developer',
                'description' => 'Berkesempatan belajar dan berkontribusi dalam pengembangan antarmuka aplikasi web menggunakan React dan TypeScript. Di bawah bimbingan senior developer.',
                'quota' => 3,
                'location' => 'Jakarta Selatan',
                'major' => $rplMajor,
                'status' => 'PUBLISHED',
                'requirements' => [
                    'Mahasiswa jurusan RPL atau sejenisnya',
                    'Mengerti dasar HTML, CSS, JavaScript',
                    'Pernah menggunakan React (nilai plus)',
                ],
                'skills' => ['React', 'JavaScript', 'HTML & CSS', 'TypeScript'],
            ],
            [
                'company' => $techCorp,
                'title' => 'Backend Developer Intern',
                'position' => 'Backend Developer',
                'description' => 'Membantu tim backend dalam mengembangkan REST API menggunakan Laravel dan MySQL. Pengalaman belajar production-ready code.',
                'quota' => 2,
                'location' => 'Jakarta Selatan',
                'major' => $rplMajor,
                'status' => 'PUBLISHED',
                'requirements' => [
                    'Mengerti PHP dasar',
                    'Pernah menggunakan framework (Laravel/CodeIgniter)',
                    'Mengerti dasar SQL',
                ],
                'skills' => ['PHP', 'Laravel', 'MySQL', 'Git'],
            ],
            // Digital Studio — Lowongan khusus SMKN 2 Bandung
            [
                'company' => $digitalStudio,
                'title' => 'UI/UX Design Intern',
                'position' => 'UI/UX Designer',
                'description' => 'Mendesain antarmuka aplikasi mobile dan web. Belajar proses desain dari wireframe hingga high-fidelity prototype menggunakan Figma.',
                'quota' => 2,
                'location' => 'Bandung',
                'major' => $mmMajor,
                'school_id' => $acceptedPartnerships->firstWhere('company_id', $digitalStudio->id)?->school_id,
                'status' => 'PUBLISHED',
                'requirements' => [
                    'Mengerti dasar desain grafis',
                    'Bisa menggunakan Figma atau tools desain lainnya',
                    'Kreatif dan teliti',
                ],
                'skills' => ['Figma', 'UI/UX Design', 'Adobe Photoshop'],
            ],
            [
                'company' => $digitalStudio,
                'title' => 'Mobile Developer Intern',
                'position' => 'Mobile Developer',
                'description' => 'Mengembangkan aplikasi mobile menggunakan Flutter. Kesempatan publish app di Play Store.',
                'quota' => 1,
                'location' => 'Bandung',
                'major' => $rplMajor,
                'school_id' => $acceptedPartnerships->firstWhere('company_id', $digitalStudio->id)?->school_id,
                'status' => 'PUBLISHED',
                'requirements' => [
                    'Mengerti dasar OOP',
                    'Pernah belajar Flutter atau Dart (nilai plus)',
                    'Rajin dan mau belajar',
                ],
                'skills' => ['Flutter', 'Dart', 'Git'],
            ],
            // MultiNet — Lowongan umum
            [
                'company' => $multiNet,
                'title' => 'Network Admin Intern',
                'position' => 'Network Administrator',
                'description' => 'Membantu tim IT ops dalam mengelola infrastruktur jaringan, server, dan troubleshooting.',
                'quota' => 2,
                'location' => 'Surabaya',
                'major' => $tkjMajor,
                'status' => 'PUBLISHED',
                'requirements' => [
                    'Jurusan TKJ atau sejenisnya',
                    'Mengerti dasar jaringan (TCP/IP, subnetting)',
                    'Pernah menggunakan Mikrotik atau Cisco (nilai plus)',
                ],
                'skills' => ['Linux', 'Mikrotik'],
            ],
            // TechCorp — draft lowongan (tidak tampil ke siswa)
            [
                'company' => $techCorp,
                'title' => 'Data Analyst Intern (Coming Soon)',
                'position' => 'Data Analyst',
                'description' => 'Lowongan ini masih dalam proses persiapan.',
                'quota' => 2,
                'location' => 'Jakarta Selatan',
                'major' => $rplMajor,
                'status' => 'DRAFT',
                'requirements' => ['Mengerti dasar statistik'],
                'skills' => ['Python', 'Data Analysis'],
            ],
        ];

        foreach ($internships as $data) {
            if (! $data['company']) {
                continue;
            }

            $internship = InternshipListing::updateOrCreate(
                [
                    'company_id' => $data['company']->id,
                    'title' => $data['title'],
                ],
                [
                    'school_id' => $data['school_id'] ?? null,
                    'major_id' => $data['major']?->id,
                    'position' => $data['position'],
                    'description' => $data['description'],
                    'quota' => $data['quota'],
                    'location' => $data['location'],
                    'period_start' => now()->addMonth(),
                    'period_end' => now()->addMonths(4),
                    'status' => $data['status'],
                ],
            );

            // Requirements
            foreach ($data['requirements'] ?? [] as $req) {
                InternshipRequirement::updateOrCreate(
                    ['internship_id' => $internship->id, 'description' => $req],
                );
            }

            // Skills
            $skillIds = Skill::whereIn('name', $data['skills'] ?? [])->pluck('id')->toArray();
            $internship->skills()->syncWithoutDetaching($skillIds);
        }
    }
}
