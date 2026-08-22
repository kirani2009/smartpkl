<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\InternshipResource;
use App\Services\SmartMatchingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 14 — Smart Matching.
 * Rekomendasi lowongan PKL berdasarkan profil siswa (rule-based scoring).
 */
class SmartMatchingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected SmartMatchingService $matchingService,
    ) {}

    /**
     * GET /api/student/matchings — rekomendasi lowongan untuk siswa.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $student->load(['school', 'major', 'skills']);

        $minScore = $request->float('min_score', 0);
        $limit = $request->integer('limit', 20);

        $recommendations = $this->matchingService->getRecommendations($student, $limit, $minScore);

        $items = $recommendations->map(fn (array $item) => [
            'internship' => new InternshipResource($item['internship']),
            'match_score' => $item['match_score'],
            'match_breakdown' => $item['match_breakdown'],
            'match_weights' => $this->matchingService->getWeights(),
        ])->values();

        return $this->success([
            'items' => $items,
            'student' => [
                'id' => $student->id,
                'major' => $student->major?->name,
                'skills_count' => $student->skills->count(),
            ],
            'scoring_info' => [
                'algorithm' => 'Rule-based scoring',
                'output' => '0-100 match percentage',
            ],
        ], 'Rekomendasi lowongan berhasil diambil.');
    }

    /**
     * GET /api/student/matchings/{internship}/detail — detail scoring untuk satu lowongan.
     */
    public function detail(Request $request, \App\Models\InternshipListing $internship): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $student->load(['school', 'major', 'skills']);
        $internship->load(['school', 'major', 'skills']);

        $match = $this->matchingService->calculateMatch($student, $internship);

        return $this->success([
            'internship' => new InternshipResource($internship),
            'match_score' => $match['score'],
            'match_breakdown' => $match['breakdown'],
            'match_weights' => $match['weights'],
        ], 'Detail scoring berhasil diambil.');
    }
}
