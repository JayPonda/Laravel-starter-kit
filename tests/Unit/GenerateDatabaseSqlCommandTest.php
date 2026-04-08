<?php

namespace Tests\Unit;

use Tests\TestCase;

class GenerateDatabaseSqlCommandTest extends TestCase
{
    public function test_generate_database_sql_command(): void
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'testdb']);
        config(['database.connections.mysql.username' => 'testuser']);
        config(['database.connections.mysql.password' => 'testpass']);
        config(['database.connections.mysql.host' => '127.0.0.1']);

        $this->artisan('db:generate-sql')
            ->expectsOutput('SQL file generated at: storage/database.sql')
            ->assertExitCode(0);

        $this->assertFileExists(storage_path('database.sql'));
    }
}