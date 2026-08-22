<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 8 — Student Profile.
 * Mengelola dokumen siswa (CV, portofolio, dll).
 * Catatan: Upload file belum diimplementasikan, hanya metadata.
 */
class StudentDocumentController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/student/documents — daftar dokumen.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $documents = $student->documents()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->latest()
            ->get();

        return $this->success([
            'items' => $documents,
        ], 'Daftar dokumen berhasil diambil.');
    }

    /**
     * POST /api/me/student/documents — tambah dokumen (metadata).
     */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $request->validate([
            'type' => ['required', 'in:CV,PORTFOLIO,OTHER'],
            'title' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
        ]);

        $document = $student->documents()->create([
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'file_path' => $request->input('file_path'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('mime_type'),
        ]);

        return $this->success($document, 'Dokumen berhasil ditambahkan.', 201);
    }

    /**
     * DELETE /api/me/student/documents/{document} — hapus dokumen.
     */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $document->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke dokumen ini.', null, 403);
        }

        $document->delete();

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
}
