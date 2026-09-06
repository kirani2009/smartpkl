<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Skill listing — readable by all authenticated users.
 * Used by company to select skills when creating internship listings.
 */
class SkillController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $skills = Skill::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('name')
            ->get();

        return $this->success($skills, 'Daftar skill berhasil diambil.');
    }
}
