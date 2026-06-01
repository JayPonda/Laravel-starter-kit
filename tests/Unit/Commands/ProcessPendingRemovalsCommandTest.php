<?php

namespace Tests\Unit\Commands;

use App\Models\File;
use App\Models\FileRemoval;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProcessPendingRemovalsCommandTest extends TestCase
{
    use DatabaseMigrations;

    private function createRemoval(string $path, string $status = FileRemoval::STATUS_PENDING): FileRemoval
    {
        $file = File::create([
            'original_name' => 'test.txt',
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);

        return FileRemoval::create([
            'file_id' => $file->id,
            'disk' => 'minio',
            'old_path' => $path,
            'status' => $status,
        ]);
    }

    public function test_dispatches_pending_removals(): void
    {
        $this->createRemoval('a.txt');
        $this->createRemoval('b.txt');

        $this->artisan('app:process-pending-removals')
            ->expectsOutput('Processing 2 pending file removals...')
            ->expectsOutput('Pending file removals have been dispatched to the queue.')
            ->assertExitCode(0);
    }

    public function test_no_pending_removals(): void
    {
        $this->artisan('app:process-pending-removals')
            ->expectsOutput('No pending file removals found.')
            ->assertExitCode(0);
    }

    public function test_skips_completed_removals(): void
    {
        $this->createRemoval('done.txt', FileRemoval::STATUS_COMPLETED);

        $this->artisan('app:process-pending-removals')
            ->expectsOutput('No pending file removals found.')
            ->assertExitCode(0);
    }
}
