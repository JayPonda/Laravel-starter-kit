<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class HealthCheckController extends Controller
{
    public function index(): View
    {
        $data = $this->getHealthData();

        return view('healthcheck', $data);
    }

    public function api(): JsonResponse
    {
        $data = $this->getHealthData();

        return response()->json($data);
    }

    private function getHealthData(): array
    {
        $database = false;
        try {
            DB::connection()->getPdo();
            $database = true;
        } catch (\Throwable $e) {
            // Database connection failed
        }

        $redis = false;
        try {
            Redis::connection()->ping();
            $redis = true;
        } catch (\Throwable $e) {
            // Redis connection failed
        }

        return [
            'status' => ($database && $redis) ? 'up' : 'partial',
            'timestamp' => now()->toDateTimeString(),
            'database' => $database,
            'redis' => $redis,
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }
}
