<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Health check untuk memastikan API berjalan.
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'app' => config('app.name'),
            'version' => '1.0.0',
            'time' => now()->toDateTimeString(),
        ], 'SmartPKL API is running.');
    }
}
