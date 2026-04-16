<?php

namespace Tests\Unit;

use Tests\TestCase;

class GenerateRedisConfigCommandTest extends TestCase
{
    public function test_generate_redis_config_script(): void
    {
        $confPath = base_path('storage/redis.conf');
        if (file_exists($confPath)) {
            unlink($confPath);
        }

        // Run the standalone script
        exec('php '.base_path('setup/generate-redis-conf.php'), $output, $returnVar);

        $this->assertEquals(0, $returnVar);
        $this->assertFileExists($confPath);

        $content = file_get_contents($confPath);
        $this->assertStringContainsString('Redis Configuration File', $content);
        $this->assertStringContainsString('requirepass', $content);
    }
}
