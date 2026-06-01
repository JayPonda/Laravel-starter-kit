<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CleanupFileJob;
use App\Models\File;
use App\Models\FileRemoval;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupFileJobTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
    }

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

    public function test_deletes_file_and_marks_completed(): void
    {
        Storage::disk('minio')->put('old/path.txt', 'content');

        $removal = $this->createRemoval('old/path.txt');

        (new CleanupFileJob($removal))->handle();

        Storage::disk('minio')->assertMissing('old/path.txt');
        $this->assertDatabaseHas('file_removals', [
            'id' => $removal->id,
            'status' => FileRemoval::STATUS_COMPLETED,
        ]);
    }

    public function test_marks_failed_on_error(): void
    {
        $removal = $this->createRemoval('missing.txt');
        $removal->update(['disk' => 'nonexistent_disk']);

        (new CleanupFileJob($removal->fresh()))->handle();

        $this->assertDatabaseHas('file_removals', [
            'id' => $removal->id,
            'status' => FileRemoval::STATUS_FAILED,
        ]);
    }
}
