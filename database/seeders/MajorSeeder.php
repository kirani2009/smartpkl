<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        // Jurusan umum (school_id null)
        $generalMajors = [
            ['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL', 'description' => 'Pengembangan perangkat lunak dan aplikasi.'],
            ['name' => 'Teknik Komputer & Jaringan', 'code' => 'TKJ', 'description' => 'Administrasi jaringan dan infrastruktur IT.'],
            ['name' => 'Multimedia', 'code' => 'MM', 'description' => 'Desain grafis, animasi, dan produksi multimedia.'],
            ['name' => 'Akuntansi', 'code' => 'AKL', 'description' => 'Pembukuan dan pengelolaan keuangan.'],
            ['name' => 'Perbankan Syariah', 'code' => 'PBS', 'description' => 'Operasional perbankan berbasis syariah.'],
            ['name' => 'Bisnis Digital', 'code' => 'BD', 'description' => 'Pemasaran digital dan e-commerce.'],
        ];

        foreach ($generalMajors as $major) {
            Major::updateOrCreate(
                ['code' => $major['code'], 'school_id' => null],
                $major,
            );
        }

        // Jurusan per sekolah (opsional — jika ada jurusan khusus sekolah)
        $schools = School::all();

        foreach ($schools as $school) {
            $schoolMajors = [
                ['name' => "DKV {$school->name}", 'code' => "DKV-{$school->npsn}", 'description' => 'Desain Komunikasi Visual khusus sekolah ini.'],
            ];

            foreach ($schoolMajors as $major) {
                Major::updateOrCreate(
                    ['code' => $major['code'], 'school_id' => $school->id],
                    $major,
                );
            }
        }
    }
}
