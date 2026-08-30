<?php

namespace App\Services;

use App\Models\InternshipListing;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * PHASE 14 — Smart Matching.
 * Rule-based scoring untuk rekomendasi lowongan PKL ke siswa.
 *
 * Bobot (docs/ai/SMART_MATCHING.json):
 *   major: 30, skills: 35, interest: 15, location: 10, period: 10
 *
 * Output: 0-100 match percentage per lowongan.
 */
class SmartMatchingService
{
    /** Bobot default (total = 100). */
    protected array $weights = [
        'major' => 30,
        'skills' => 35,
        'interest' => 15,
        'location' => 10,
        'period' => 10,
    ];

    /**
     * Dapatkan bobot scoring saat ini.
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Hitung match score untuk satu siswa dan satu lowongan.
     *
     * @return array{score: float, breakdown: array}
     */
    public function calculateMatch(Student $student, InternshipListing $internship): array
    {
        $breakdown = [];

        // 1. Major match (30)
        $breakdown['major'] = $this->scoreMajor($student, $internship);

        // 2. Skills match (35)
        $breakdown['skills'] = $this->scoreSkills($student, $internship);

        // 3. Interest match (15)
        $breakdown['interest'] = $this->scoreInterest($student, $internship);

        // 4. Location match (10)
        $breakdown['location'] = $this->scoreLocation($student, $internship);

        // 5. Period match (10)
        $breakdown['period'] = $this->scorePeriod($student, $internship);

        $totalScore = array_sum($breakdown);

        return [
            'score' => round($totalScore, 1),
            'breakdown' => $breakdown,
            'weights' => $this->weights,
        ];
    }

    /**
     * Dapatkan rekomendasi lowongan untuk siswa (sorted by score DESC).
     *
     * @param int $limit Jumlah rekomendasi teratas.
     * @param float $minScore Score minimum agar masuk rekomendasi.
     * @return Collection
     */
    public function getRecommendations(Student $student, int $limit = 20, float $minScore = 0): Collection
    {
        $internships = InternshipListing::query()
            ->with(['company.profile', 'school:id,name', 'major:id,name', 'skills'])
            ->where('status', InternshipListing::STATUS_PUBLISHED)
            ->get();

        $recommendations = $internships->map(function (InternshipListing $internship) use ($student) {
            $match = $this->calculateMatch($student, $internship);

            return [
                'internship' => $internship,
                'match_score' => $match['score'],
                'match_breakdown' => $match['breakdown'],
            ];
        })
        ->filter(fn (array $item) => $item['match_score'] >= $minScore)
        ->sortByDesc('match_score')
        ->values()
        ->take($limit);

        return $recommendations;
    }

    // ---- Scoring Functions ----

    /**
     * Major match: 30 jika jurusan sama, 0 jika tidak ada, proporsional jika ada.
     */
    protected function scoreMajor(Student $student, InternshipListing $internship): float
    {
        $weight = $this->weights['major'];

        // Jika lowongan tidak spesifik jurusan → full score
        if ($internship->major_id === null) {
            return $weight;
        }

        // Jika jurusan sama → full score
        if ($student->major_id === $internship->major_id) {
            return $weight;
        }

        return 0;
    }

    /**
     * Skills match: 35 jika ada skill overlap dengan lowongan.
     */
    protected function scoreSkills(Student $student, InternshipListing $internship): float
    {
        $weight = $this->weights['skills'];

        $internshipSkillIds = $internship->skills->pluck('id')->toArray();

        // Jika lowongan tidak butuh skill → full score
        if (empty($internshipSkillIds)) {
            return $weight;
        }

        $studentSkillIds = $student->skills->pluck('id')->toArray();

        // Jika siswa tidak punya skill → 0
        if (empty($studentSkillIds)) {
            return 0;
        }

        $overlap = count(array_intersect($studentSkillIds, $internshipSkillIds));
        $total = count($internshipSkillIds);

        // Score proporsional: skill overlap / total skill lowongan
        return round(($overlap / $total) * $weight, 1);
    }

    /**
     * Interest match: 15 jika minat siswa ada di deskripsi/posisi lowongan.
     */
    protected function scoreInterest(Student $student, InternshipListing $internship): float
    {
        $weight = $this->weights['interest'];

        $interests = strtolower(trim($student->interests ?? ''));
        if ($interests === '') {
            return $weight / 2; // Tidak ada data → setengah score
        }

        $interestWords = array_filter(explode(',', $interests), fn ($w) => trim($w) !== '');
        $targetText = strtolower(($internship->title ?? '') . ' ' . ($internship->position ?? '') . ' ' . ($internship->description ?? ''));

        $matches = 0;
        foreach ($interestWords as $word) {
            if (str_contains($targetText, trim($word))) {
                $matches++;
            }
        }

        if (empty($interestWords)) {
            return $weight / 2;
        }

        return round(($matches / count($interestWords)) * $weight, 1);
    }

    /**
     * Location match: 10 jika lokasi lowongan ada di kota yang sama dengan sekolah.
     */
    protected function scoreLocation(Student $student, InternshipListing $internship): float
    {
        $weight = $this->weights['location'];

        $studentCity = strtolower(trim($student->school?->city ?? ''));
        $internshipLocation = strtolower(trim($internship->location ?? ''));

        // Jika salah satu tidak ada data → setengah score
        if ($studentCity === '' || $internshipLocation === '') {
            return $weight / 2;
        }

        // Partial match: kota siswa ada di lokasi lowongan atau sebaliknya
        if (str_contains($internshipLocation, $studentCity) || str_contains($studentCity, $internshipLocation)) {
            return $weight;
        }

        return 0;
    }

    /**
     * Period match: 10 jika periode lowongan tersisa >= 2 minggu.
     */
    protected function scorePeriod(Student $student, InternshipListing $internship): float
    {
        $weight = $this->weights['period'];

        $now = now();
        $periodEnd = $internship->period_end;

        if (! $periodEnd) {
            return $weight / 2; // Tidak ada data periode
        }

        // Masih tersisa >= 14 hari
        $daysRemaining = $now->diffInDays($periodEnd, false);
        if ($daysRemaining >= 14) {
            return $weight;
        }

        // Masih tersisa >= 7 hari → setengah
        if ($daysRemaining >= 7) {
            return round($weight / 2, 1);
        }

        // Kurang dari 7 hari → 0
        return 0;
    }
}
