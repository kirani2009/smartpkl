<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Major;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Password plaintext; model cast 'hashed' akan meng-hash otomatis.
        $password = 'password123';

        // ---- ADMIN ----
        User::updateOrCreate(
            ['email' => 'admin@smartpkl.id'],
            [
                'name' => 'Admin SmartPKL',
                'password' => $password,
                'role' => 'admin',
            ],
        );

        // ---- GURU (1 guru per sekolah) ----
        $schools = School::all();
        $teachers = collect();

        $teacherData = [
            ['name' => 'Pak Budi Santoso', 'email' => 'guru@smartpkl.id', 'nip' => '198501012010011001', 'position' => 'Wali Kelas XII'],
            ['name' => 'Bu Siti Rahmawati', 'email' => 'guru2@smartpkl.id', 'nip' => '198803152012012001', 'position' => 'Guru BK'],
            ['name' => 'Pak Andi Pratama', 'email' => 'guru3@smartpkl.id', 'nip' => '199005202015011001', 'position' => 'Koordinator PKL'],
        ];

        foreach ($schools as $i => $school) {
            $data = $teacherData[$i] ?? $teacherData[0];

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'role' => 'teacher',
                ],
            );

            $teacher = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $school->id,
                    'nip' => $data['nip'],
                    'position' => $data['position'],
                    'phone' => '0812' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                ],
            );

            $teachers->push($teacher);
        }

        // ---- SISWA (3 siswa per sekolah) ----
        $majors = Major::whereNotNull('school_id')->get()->merge(
            Major::whereNull('school_id')->take(2)->get()
        );
        $skills = Skill::all();
        $students = collect();

        $studentNames = [
            ['Rina Wulandari', 'rina@smartpkl.id', 'female'],
            ['Dedi Kurniawan', 'dedi@smartpkl.id', 'male'],
            ['Maya Sari', 'maya@smartpkl.id', 'female'],
            ['Rizky Pratama', 'rizky@smartpkl.id', 'male'],
            ['Diana Putri', 'diana@smartpkl.id', 'female'],
            ['Fajar Nugroho', 'fajar@smartpkl.id', 'male'],
            ['Lestari Dewi', 'lestari@smartpkl.id', 'female'],
            ['Adi Saputra', 'adi@smartpkl.id', 'male'],
            ['Nina Kartika', 'nina@smartpkl.id', 'female'],
        ];

        $idx = 0;
        foreach ($schools as $school) {
            for ($j = 0; $j < 3 && $idx < count($studentNames); $j++, $idx++) {
                $sData = $studentNames[$idx];
                $major = $majors->random();

                $user = User::updateOrCreate(
                    ['email' => $sData[1]],
                    [
                        'name' => $sData[0],
                        'password' => $password,
                        'role' => 'student',
                    ],
                );

                $student = Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'school_id' => $school->id,
                        'major_id' => $major->id,
                        'nis' => str_pad(2024000 + $idx, 7, '0', STR_PAD_LEFT),
                        'class' => 'XII ' . $major->code . ' ' . ($j + 1),
                        'entry_year' => 2023,
                        'gender' => $sData[2],
                        'birth_date' => '2006-' . str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT),
                        'phone' => '0857' . str_pad($idx + 1, 8, '0', STR_PAD_LEFT),
                        'address' => "Jl. Contoh No. {$idx}, {$school->city}",
                        'interests' => $skills->random(min(3, $skills->count()))->pluck('name')->join(', '),
                    ],
                );

                // Attach random skills
                $student->skills()->syncWithoutDetaching(
                    $skills->random(min(mt_rand(2, 4), $skills->count()))->pluck('id')->toArray()
                );

                $students->push($student);
            }
        }

        // ---- PERUSAHAAN (3 perusahaan) ----
        // Mapping perusahaan ke sekolah (satu perusahaan = satu sekolah)
        $companyData = [
            [
                'email' => 'hrd@techcorp.id',
                'name' => 'PT TechCorp Indonesia',
                'industry' => 'Technology',
                'address' => 'Jl. Sudirman Kav. 52-53',
                'city' => 'Jakarta Selatan',
                'phone' => '021-5785400',
                'website' => 'https://techcorp.id',
                'description' => 'Perusahaan teknologi terkemuka yang fokus pada pengembangan solusi digital enterprise.',
                'established_year' => 2015,
                'employee_count' => 250,
                'school_npsn' => '20109876', // SMKN 1 Jakarta
            ],
            [
                'email' => 'info@digitalstudio.id',
                'name' => 'Digital Studio Nusantara',
                'industry' => 'Digital Creative',
                'address' => 'Jl. Buah Batu No. 120',
                'city' => 'Bandung',
                'phone' => '022-7301234',
                'website' => 'https://digitalstudio.id',
                'description' => 'Studio kreatif digital yang melayani desain UI/UX, branding, dan pengembangan aplikasi mobile.',
                'established_year' => 2018,
                'employee_count' => 45,
                'school_npsn' => '20205432', // SMKN 2 Bandung
            ],
            [
                'email' => 'recruit@multinet.id',
                'name' => 'PT MultiNet Solutions',
                'industry' => 'IT Services',
                'address' => 'Jl. Pemuda No. 88',
                'city' => 'Surabaya',
                'phone' => '031-5678900',
                'website' => 'https://multinet.id',
                'description' => 'Perusahaan jasa IT yang menyediakan layanan managed services, cloud, dan cybersecurity.',
                'established_year' => 2012,
                'employee_count' => 120,
                'school_npsn' => '20309999', // SMKN 3 Surabaya
            ],
        ];

        foreach ($companyData as $cData) {
            $user = User::updateOrCreate(
                ['email' => $cData['email']],
                [
                    'name' => $cData['name'],
                    'password' => $password,
                    'role' => 'company',
                ],
            );

            // Cari school_id berdasarkan NPSN
            $school = School::where('npsn', $cData['school_npsn'])->first();

            $company = Company::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'active',
                    'school_id' => $school?->id,
                ],
            );

            CompanyProfile::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'name' => $cData['name'],
                    'industry' => $cData['industry'],
                    'address' => $cData['address'],
                    'city' => $cData['city'],
                    'phone' => $cData['phone'],
                    'email' => $cData['email'],
                    'website' => $cData['website'],
                    'description' => $cData['description'],
                    'established_year' => $cData['established_year'],
                    'employee_count' => $cData['employee_count'],
                ],
            );
        }
    }
}
