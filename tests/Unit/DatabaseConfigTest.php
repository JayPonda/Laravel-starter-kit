<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DatabaseConfigTest extends TestCase
{
    public function test_uses_sqlite_in_memory_for_testing(): void
    {
        $this->assertEquals('sqlite', Config::get('database.default'));
        $this->assertEquals(':memory:', Config::get('database.connections.sqlite.database'));
    }
}
