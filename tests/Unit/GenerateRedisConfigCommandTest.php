<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GenerateRedisConfigCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_generate_redis_config_command(): void
    {
        $this->artisan('redis:generate-config')
            ->expectsOutput('Redis config generated at: storage/redis.conf')
            ->assertExitCode(0);

        $this->assertFileExists(storage_path('redis.conf'));
    }
}
