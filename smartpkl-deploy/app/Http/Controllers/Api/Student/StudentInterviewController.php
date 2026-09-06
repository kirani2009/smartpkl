<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterviewResource;
use App\Models\Interview;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 11 — Interview.
 * Siswa melihat jadwal interview milik sendiri.
 */
class StudentInterviewController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/student/interviews — daftar interview siswa.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $interviews = Interview::query()
            ->with([
                'application.internship.company.profile',
                'application.internship.school',
            ])
            ->whereHas('application', fn ($q) => $q->where('student_id', $student->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('scheduled_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => InterviewResource::collection($interviews),
            'meta' => [
                'current_page' => $interviews->currentPage(),
                'last_page' => $interviews->lastPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
            ],
        ], 'Daftar interview berhasil diambil.');
    }

    /**
     * GET /api/student/interviews/{interview} — detail interview.
     */
    public function show(Request $request, Interview $interview): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $interview->application->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke interview ini.', null, 403);
        }

        $interview->load([
            'application.internship.company.profile',
            'application.internship.school',
            'application.internship.major',
            'application.internship.requirements',
        ]);

        return $this->success(new InterviewResource($interview), 'Detail interview berhasil diambil.');
    }
}
