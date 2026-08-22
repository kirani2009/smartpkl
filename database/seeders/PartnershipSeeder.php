<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class PartnershipSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $schools = School::all();

        if ($companies->isEmpty() || $schools->isEmpty()) {
            return;
        }

        $partnerships = [
            // SMKN 1 Jakarta <-> TechCorp (ACTIVE)
            [
                'school' => $schools->firstWhere('npsn', '20109876'),
                'company' => $companies->firstWhere('user.email', 'hrd@techcorp.id'),
                'status' => SchoolCompanyPartnership::STATUS_ACCEPTED,
            ],
            // SMKN 2 Bandung <-> Digital Studio (ACTIVE)
            [
                'school' => $schools->firstWhere('npsn', '20205432'),
                'company' => $companies->firstWhere('user.email', 'info@digitalstudio.id'),
                'status' => SchoolCompanyPartnership::STATUS_ACCEPTED,
            ],
            // SMKN 3 Surabaya <-> MultiNet (PENDING)
            [
                'school' => $schools->firstWhere('npsn', '20309999'),
                'company' => $companies->firstWhere('user.email', 'recruit@multinet.id'),
                'status' => SchoolCompanyPartnership::STATUS_PENDING,
            ],
            // SMKN 1 Jakarta <-> Digital Studio (PENDING)
            [
                'school' => $schools->firstWhere('npsn', '20109876'),
                'company' => $companies->firstWhere('user.email', 'info@digitalstudio.id'),
                'status' => SchoolCompanyPartnership::STATUS_PENDING,
            ],
        ];

        foreach ($partnerships as $p) {
            if (! $p['school'] || ! $p['company']) {
                continue;
            }

            // Find the teacher who requested this partnership
            $teacher = Teacher::where('school_id', $p['school']->id)->first();

            SchoolCompanyPartnership::updateOrCreate(
                [
                    'school_id' => $p['school']->id,
                    'company_id' => $p['company']->id,
                ],
                [
                    'status' => $p['status'],
                    'requested_by' => $teacher?->user_id,
                    'notes' => $p['status'] === SchoolCompanyPartnership::STATUS_ACCEPTED
                        ? 'Partnership sudah disetujui.'
                        : 'Menunggu persetujuan perusahaan.',
                    'responded_at' => $p['status'] === SchoolCompanyPartnership::STATUS_ACCEPTED ? now() : null,
                ],
            );
        }
    }
}
