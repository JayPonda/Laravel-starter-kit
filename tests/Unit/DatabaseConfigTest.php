<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class DatabaseConfigTest extends TestCase
{
    public function test_uses_sqlite_in_memory_for_testing(): void
    {
        $this->assertEquals('sqlite', Config::get('database.default'));
        $this->assertEquals(':memory:', Config::get('database.connections.sqlite.database'));
    }
}