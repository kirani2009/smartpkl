<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            [
                'name' => 'SMKN 1 Jakarta',
                'npsn' => '20109876',
                'address' => 'Jl. Pemuda No. 10, Rawamangun',
                'city' => 'Jakarta Timur',
                'phone' => '021-4891234',
                'email' => 'info@smkn1jakarta.sch.id',
                'description' => 'Sekolah menengah kejuruan negeri unggulan di Jakarta Timur.',
            ],
            [
                'name' => 'SMKN 2 Bandung',
                'npsn' => '20205432',
                'address' => 'Jl. Dobbpot No. 68, Cimahi',
                'city' => 'Bandung',
                'phone' => '022-6654321',
                'email' => 'info@smkn2bandung.sch.id',
                'description' => 'SMK negeri dengan program keahlian Teknologi Informasi.',
            ],
            [
                'name' => 'SMKN 3 Surabaya',
                'npsn' => '20309999',
                'address' => 'Jl. Prof. Dr. Soetomo No. 35',
                'city' => 'Surabaya',
                'phone' => '031-5678900',
                'email' => 'info@smkn3surabaya.sch.id',
                'description' => 'SMK unggulan di Surabaya dengan jurusan Rekayasa Perangkat Lunak.',
            ],
        ];

        foreach ($schools as $school) {
            School::updateOrCreate(
                ['npsn' => $school['npsn']],
                $school,
            );
        }
    }
}
