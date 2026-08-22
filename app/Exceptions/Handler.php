<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 404 pada API tetap mengikuti struktur response standar.
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource tidak ditemukan.',
                    'data' => null,
                    'errors' => null,
                ], 404);
            }
        });

        // 403 dari Policy/Gate (prepareException mengubah AuthorizationException
        // menjadi AccessDeniedHttpException) mengikuti struktur response standar.
        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Anda tidak memiliki akses ke resource ini.',
                    'data' => null,
                    'errors' => null,
                ], 403);
            }
        });
    }

    /**
     * Response error validasi mengikuti struktur standar API
     * { success, message, data, errors } (docs/ai/API.json).
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'data' => null,
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Response 401 mengikuti struktur standar API.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated: silakan login terlebih dahulu.',
            'data' => null,
            'errors' => null,
        ], 401);
    }
}
