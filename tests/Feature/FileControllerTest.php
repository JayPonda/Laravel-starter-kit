<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
    }

    public function test_user_can_upload_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/files', [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'File uploaded successfully')
            ->assertJsonStructure([
                'file' => ['id', 'original_name', 'path', 'mime_type', 'size']
            ]);

        $this->assertDatabaseHas('files', [
            'original_name' => 'document.pdf',
        ]);

        $this->assertDatabaseHas('file_user', [
            'user_id' => $user->id,
            'permission' => 'owner',
        ]);

        $datePath = now()->format('Y-m-d');
        Storage::disk('minio')->assertExists("file-upload/{$datePath}/" . $file->hashName());
    }

    public function test_user_can_list_only_their_files()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // File for User 1
        $file1 = File::create([
            'original_name' => 'user1_file.txt',
            'path' => 'uploads/f1.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);
        $user1->files()->attach($file1->id, ['permission' => 'owner']);

        // File for User 2
        $file2 = File::create([
            'original_name' => 'user2_file.txt',
            'path' => 'uploads/f2.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);
        $user2->files()->attach($file2->id, ['permission' => 'owner']);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['original_name' => 'user1_file.txt'])
            ->assertJsonMissing(['original_name' => 'user2_file.txt']);
    }

    public function test_user_cannot_access_unauthorized_file()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $file = File::create([
            'original_name' => 'secret.pdf',
            'path' => 'uploads/secret.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/files/{$file->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized access to this file.');
    }

    public function test_user_can_view_authorized_file()
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'shared.pdf',
            'path' => 'uploads/shared.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);
        $user->files()->attach($file->id, ['permission' => 'viewer']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJsonPath('original_name', 'shared.pdf');
    }

    public function test_user_cannot_delete_with_viewer_permission()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $file = File::create([
            'original_name' => 'protected.jpg',
            'path' => 'uploads/protected.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);
        
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $viewer->files()->attach($file->id, ['permission' => 'viewer']);

        $response = $this->actingAs($viewer, 'sanctum')
            ->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You do not have the required permissions.');
    }

    public function test_only_owner_can_delete_file()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $file = File::create([
            'original_name' => 'delete_me.jpg',
            'path' => 'uploads/delete_me.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $viewer->files()->attach($file->id, ['permission' => 'viewer']);

        // Viewer tries to delete
        $this->actingAs($viewer, 'sanctum')
            ->deleteJson("/api/files/{$file->id}")
            ->assertStatus(403);

        // Owner deletes
        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/files/{$file->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('minio')->assertMissing('uploads/delete_me.jpg');
    }

    public function test_web_index_shows_files_categorized()
    {
        $user = User::factory()->create();

        $editable = File::create([
            'original_name' => 'editable.txt',
            'path' => 'p1', 'mime_type' => 'text/plain', 'size' => 10
        ]);
        $user->files()->attach($editable->id, ['permission' => 'editor']);

        $viewOnly = File::create([
            'original_name' => 'readonly.txt',
            'path' => 'p2', 'mime_type' => 'text/plain', 'size' => 10
        ]);
        $user->files()->attach($viewOnly->id, ['permission' => 'viewer']);

        $response = $this->actingAs($user)
            ->get('/files');

        $response->assertStatus(200)
            ->assertViewHas('editableFiles')
            ->assertViewHas('viewOnlyFiles');

        $this->assertCount(1, $response->viewData('editableFiles'));
        $this->assertCount(1, $response->viewData('viewOnlyFiles'));
    }

    public function test_web_upload_redirects_back()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('web_doc.pdf', 500);

        $response = $this->actingAs($user)
            ->post('/files', [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File uploaded successfully!');

        $this->assertDatabaseHas('files', ['original_name' => 'web_doc.pdf']);
    }

    public function test_web_delete_redirects_back()
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'web_delete.txt',
            'path' => 'uploads/web_delete.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($user)
            ->delete("/files/{$file->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File deleted successfully!');

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}

