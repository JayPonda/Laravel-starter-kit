<?php

namespace Tests\Unit;

use Tests\TestCase;

class GenerateDatabaseSqlCommandTest extends TestCase
{
    public function test_generate_database_sql_script(): void
    {
        $sqlPath = base_path('storage/database.sql');
        if (file_exists($sqlPath)) {
            unlink($sqlPath);
        }

        // Run the standalone script
        exec('php '.base_path('setup/generate-db-sql.php'), $output, $returnVar);

        $this->assertEquals(0, $returnVar);
        $this->assertFileExists($sqlPath);

        $content = file_get_contents($sqlPath);
        $this->assertStringContainsString('CREATE DATABASE IF NOT EXISTS', $content);
        $this->assertStringContainsString('CREATE USER IF NOT EXISTS', $content);
    }
}
