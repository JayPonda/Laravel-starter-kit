<?php

namespace Tests\Unit\Models;

use App\Models\File;
use App\Models\FileRemoval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_belongs_to_many_users(): void
    {
        $file = File::create([
            'original_name' => 'test.txt',
            'path' => 'uploads/test.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);

        $user = User::factory()->create();
        $file->users()->attach($user->id, ['permission' => 'owner']);

        $this->assertInstanceOf(User::class, $file->users->first());
        $this->assertEquals('owner', $file->users->first()->pivot->permission);
    }

    public function test_user_belongs_to_many_files(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'test.txt',
            'path' => 'uploads/test.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);

        $user->files()->attach($file->id, ['permission' => 'owner']);

        $this->assertInstanceOf(File::class, $user->files->first());
        $this->assertEquals('owner', $user->files->first()->pivot->permission);
    }

    public function test_file_has_many_file_removals(): void
    {
        $file = File::create([
            'original_name' => 'removal_test.txt',
            'path' => 'uploads/removal_test.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);

        $removal = FileRemoval::create([
            'file_id' => $file->id,
            'disk' => 'minio',
            'old_path' => 'uploads/old.txt',
            'status' => FileRemoval::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(FileRemoval::class, $file->fileRemovals->first());
        $this->assertEquals($removal->id, $file->fileRemovals->first()->id);
    }
}
