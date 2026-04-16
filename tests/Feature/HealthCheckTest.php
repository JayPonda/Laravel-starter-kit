<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_web_health_check_returns_success(): void
    {
        Redis::shouldReceive('connection')->andReturnSelf();
        Redis::shouldReceive('ping')->andReturn(true);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Health Check');
        $response->assertSee('System UP');
    }

    public function test_api_health_check_returns_json(): void
    {
        Redis::shouldReceive('connection')->andReturnSelf();
        Redis::shouldReceive('ping')->andReturn(true);

        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'up',
                'database' => true,
                'redis' => true,
            ]);
    }

    public function test_health_check_with_database_failure(): void
    {
        Redis::shouldReceive('connection')->andReturnSelf();
        Redis::shouldReceive('ping')->andReturn(true);

        // Mock DB to fail
        DB::shouldReceive('connection')->andThrow(new \Exception('DB Error'));

        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'partial',
                'database' => false,
                'redis' => true,
            ]);
    }

    public function test_health_check_with_redis_failure(): void
    {
        // Force database to be up for this test
        DB::shouldReceive('connection->getPdo')->andReturn(true);

        // Mock Redis to fail
        Redis::shouldReceive('connection')->andThrow(new \Exception('Redis Error'));

        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'partial',
                'database' => true,
                'redis' => false,
            ]);
    }
}
