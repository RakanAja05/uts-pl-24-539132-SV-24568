<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Perform deep health check for database and cache services.
     */
    public function check(Request $request): JsonResponse
    {
        $services = [];
        $isHealthy = true;
        $failedServices = [];

        // 1. Check Database connection & query execution
        try {
            DB::select('SELECT 1');
            $services['database'] = [
                'status' => 'up',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $services['database'] = [
                'status' => 'down',
                'reason' => $e->getMessage(),
            ];
            $failedServices['database'] = $e->getMessage();
        }

        // 2. Check Cache read and write operations
        try {
            $testKey = 'health_check_' . uniqid('', true);
            Cache::put($testKey, 'ok', 5);
            $cachedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($cachedValue !== 'ok') {
                throw new \RuntimeException('Cache write/read verification returned invalid value.');
            }

            $services['cache'] = [
                'status' => 'up',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $services['cache'] = [
                'status' => 'down',
                'reason' => $e->getMessage(),
            ];
            $failedServices['cache'] = $e->getMessage();
        }

        $timestamp = now()->toIso8601String();

        if ($isHealthy) {
            return response()->json([
                'status' => 'healthy',
                'timestamp' => $timestamp,
                'services' => $services,
            ], 200);
        }

        // System Failure: Log structured error when any critical service is down
        Log::error('Health check failed: system service unavailable', [
            'who' => [
                'user_id' => auth()->id() ?? 'guest',
                'ip' => $request->ip(),
            ],
            'what' => [
                'action' => 'deep_health_check',
                'endpoint' => $request->path(),
            ],
            'why' => [
                'reason' => 'One or more required subsystem checks failed',
                'failures' => $failedServices,
            ],
        ]);

        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => $timestamp,
            'services' => $services,
        ], 503);
    }
}
