<?php

namespace Tests\Unit;

use App\Models\File;
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
}
