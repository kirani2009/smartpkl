<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\InternshipListing;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        // Get published internships
        $internships = InternshipListing::where('status', 'PUBLISHED')->get();
        $students = Student::with('user')->get();

        if ($internships->isEmpty() || $students->isEmpty()) {
            return;
        }

        // Apply some students to some internships
        $applications = [
            // Student 1 applies to Frontend Dev Intern
            [
                'studentEmail' => 'rina@smartpkl.id',
                'internshipTitle' => 'Frontend Developer Intern',
                'status' => Application::STATUS_PENDING,
                'message' => 'Saya tertarik dengan frontend development. Saya sudah belajar React selama 6 bulan.',
            ],
            // Student 2 applies to Frontend Dev Intern — already reviewed
            [
                'studentEmail' => 'dedi@smartpkl.id',
                'internshipTitle' => 'Frontend Developer Intern',
                'status' => Application::STATUS_ACCEPTED,
                'message' => 'Saya memiliki pengalaman membuat proyek web sekolah menggunakan HTML, CSS, dan JavaScript.',
            ],
            // Student 3 applies to Backend Dev Intern
            [
                'studentEmail' => 'maya@smartpkl.id',
                'internshipTitle' => 'Backend Developer Intern',
                'status' => Application::STATUS_REVIEWED,
                'message' => 'Saya sudah familiar dengan PHP dan pernah membuat REST API sederhana.',
            ],
            // Student 4 applies to UI/UX Design Intern
            [
                'studentEmail' => 'rizky@smartpkl.id',
                'internshipTitle' => 'UI/UX Design Intern',
                'status' => Application::STATUS_PENDING,
                'message' => 'Saya senang mendesain dan sudah menggunakan Figma untuk proyek sekolah.',
            ],
            // Student 5 applies to Network Admin Intern
            [
                'studentEmail' => 'fajar@smartpkl.id',
                'internshipTitle' => 'Network Admin Intern',
                'status' => Application::STATUS_ACCEPTED,
                'message' => 'Saya jurusan TKJ dan sudah sertifikasi Mikrotik MTCNA.',
            ],
        ];

        foreach ($applications as $app) {
            $student = $students->firstWhere('user.email', $app['studentEmail']);
            $internship = $internships->firstWhere('title', $app['internshipTitle']);

            if (! $student || ! $internship) {
                continue;
            }

            $application = Application::updateOrCreate(
                ['internship_id' => $internship->id, 'student_id' => $student->id],
                [
                    'status' => $app['status'],
                    'message' => $app['message'],
                    'applied_at' => now()->subDays(mt_rand(1, 7)),
                ],
            );

            // Create status history
            ApplicationStatusHistory::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'status' => Application::STATUS_PENDING,
                ],
                [
                    'changed_by' => $student->user_id,
                    'note' => 'Lamaran dikirim.',
                ],
            );

            if ($app['status'] !== Application::STATUS_PENDING) {
                ApplicationStatusHistory::updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'status' => $app['status'],
                    ],
                    [
                        'changed_by' => $internship->company->user_id,
                        'note' => $app['status'] === Application::STATUS_ACCEPTED
                            ? 'Lamaran diterima. Selamat!'
                            : 'Lamaran sedang direview.',
                    ],
                );
            }
        }
    }
}
