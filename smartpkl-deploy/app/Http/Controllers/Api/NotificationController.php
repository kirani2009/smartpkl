<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 13 — Notification.
 * Notifikasi user: daftar, baca, tandai sudah dibaca, jumlah belum dibaca.
 */
class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/notifications — daftar notifikasi user.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('unread_only'), function ($q) {
                $q->whereNull('read_at');
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success([
            'items' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ], 'Daftar notifikasi berhasil diambil.');
    }

    /**
     * GET /api/notifications/unread-count — jumlah notifikasi belum dibaca.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->success([
            'count' => $count,
        ], 'Jumlah notifikasi belum dibaca berhasil diambil.');
    }

    /**
     * GET /api/notifications/{notification} — detail notifikasi.
     */
    public function show(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Anda tidak memiliki akses ke notifikasi ini.', null, 403);
        }

        return $this->success(new NotificationResource($notification), 'Detail notifikasi berhasil diambil.');
    }

    /**
     * PUT /api/notifications/{notification}/read — tandai sudah dibaca.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Anda tidak memiliki akses ke notifikasi ini.', null, 403);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $this->success(new NotificationResource($notification->fresh()), 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * PUT /api/notifications/read-all — tandai semua sudah dibaca.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success([
            'updated' => $updated,
        ], "{$updated} notifikasi ditandai sudah dibaca.");
    }

    /**
     * DELETE /api/notifications/{notification} — hapus notifikasi.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Anda tidak memiliki akses ke notifikasi ini.', null, 403);
        }

        $notification->delete();

        return $this->success(null, 'Notifikasi berhasil dihapus.');
    }
}
