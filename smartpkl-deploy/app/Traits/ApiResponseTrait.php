<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Standard API response helper.
 *
 * Response structure (docs/ai/API.json):
 * {
 *   "success": true|false,
 *   "message": "Human readable message",
 *   "data": {...}|null,
 *   "errors": {...}|null
 * }
 */
trait ApiResponseTrait
{
    /**
     * Return a standard success response.
     */
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /**
     * Return a standard error response.
     */
    protected function error(string $message = 'Error', mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
