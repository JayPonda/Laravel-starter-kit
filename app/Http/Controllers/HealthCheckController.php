<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Health', description: 'Service health checks')]
class HealthCheckController extends Controller
{
    public function index(): View
    {
        $data = $this->getHealthData();

        return view('healthcheck', $data);
    }

    #[OA\Get(
        path: '/',
        summary: 'API health check',
        tags: ['Health'],
        responses: [
            new OA\Response(response: 200, description: 'Service health', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['up', 'partial'], example: 'up'),
                new OA\Property(property: 'timestamp', type: 'string', example: '2026-08-25 12:00:00'),
                new OA\Property(property: 'database', type: 'boolean', example: true),
                new OA\Property(property: 'redis', type: 'boolean', example: true),
                new OA\Property(property: 'laravel_version', type: 'string', example: '13.0.0'),
                new OA\Property(property: 'php_version', type: 'string', example: '8.5.8'),
            ])),
        ]
    )]
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
