<?php

namespace Tests\Unit\Commands\Make;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = app_path('Console/Commands/TestImport.php');
        if (File::exists($path)) {
            File::delete($path);
        }
        parent::tearDown();
    }

    public function test_generates_basic_command(): void
    {
        $this->artisan('generate:command', [
            'name' => 'TestImport',
            'signature' => 'app:test-import',
        ])
            ->expectsOutput('Created Command: TestImport')
            ->expectsOutput('Signature: app:test-import')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Console/Commands/TestImport.php'));
    }

    public function test_generates_command_with_file_read(): void
    {
        $this->artisan('generate:command', [
            'name' => 'TestCsvImport',
            'signature' => 'app:csv-import',
            '--file-read' => true,
        ])
            ->assertExitCode(0);

        $content = File::get(app_path('Console/Commands/TestCsvImport.php'));
        $this->assertStringContainsString('use Illuminate\Support\Facades\File;', $content);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Storage;', $content);
        $this->assertStringContainsString('protected function handleRow', $content);
        File::delete(app_path('Console/Commands/TestCsvImport.php'));
    }

    public function test_generates_command_with_batch(): void
    {
        $this->artisan('generate:command', [
            'name' => 'TestBatchImport',
            'signature' => 'app:batch-import',
            '--file-read' => true,
            '--batch' => '100',
        ])
            ->assertExitCode(0);

        $content = File::get(app_path('Console/Commands/TestBatchImport.php'));
        $this->assertStringContainsString('protected function handleBatch', $content);
        File::delete(app_path('Console/Commands/TestBatchImport.php'));
    }

    public function test_prevents_duplicate(): void
    {
        File::put(app_path('Console/Commands/TestDup.php'), '<?php ');

        $this->artisan('generate:command', [
            'name' => 'TestDup',
            'signature' => 'app:dup',
        ])
            ->expectsOutput('Command TestDup already exists!')
            ->assertExitCode(1);

        File::delete(app_path('Console/Commands/TestDup.php'));
    }
}
