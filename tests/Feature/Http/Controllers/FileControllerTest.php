<?php

namespace Tests\Feature\Http\Controllers;

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
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('minio');
        $storage->assertExists("file-upload/{$datePath}/" . $file->hashName());
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

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('minio');
        $storage->assertMissing('uploads/delete_me.jpg');
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
        $response->assertSessionHas('success', 'File deletion initiated!');

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_owner_can_share_file_with_editor(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'share.txt', 'path' => 's.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/files/{$file->id}/share", [
                'user_id' => $other->id,
                'permission' => 'editor',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'File shared successfully');
        $this->assertDatabaseHas('file_user', [
            'file_id' => $file->id, 'user_id' => $other->id, 'permission' => 'editor',
        ]);
    }

    public function test_owner_can_revoke_share(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'revoke.txt', 'path' => 'r.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $other->files()->attach($file->id, ['permission' => 'viewer']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/files/{$file->id}/share", [
                'user_id' => $other->id,
                'permission' => 'none',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Access revoked successfully');
        $this->assertDatabaseMissing('file_user', ['file_id' => $file->id, 'user_id' => $other->id]);
    }

    public function test_owner_can_unshare(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'unshare.txt', 'path' => 'u.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $other->files()->attach($file->id, ['permission' => 'editor']);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/files/{$file->id}/share/{$other->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Access revoked successfully');
        $this->assertDatabaseMissing('file_user', ['file_id' => $file->id, 'user_id' => $other->id]);
    }

    public function test_editor_can_update_file_content(): void
    {
        $owner = User::factory()->create();
        $file = File::create(['original_name' => 'edit.txt', 'path' => 'uploads/edit.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        Storage::disk('minio')->put('uploads/edit.txt', 'original content');

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/files/{$file->id}", ['content' => 'updated content']);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'File saved successfully');
        $this->assertDatabaseMissing('files', ['path' => 'uploads/edit.txt']);
    }

    public function test_web_share_redirects_back(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'web_share.txt', 'path' => 'ws.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($owner)
            ->post("/files/{$file->id}/share", [
                'user_id' => $other->id,
                'permission' => 'editor',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File shared successfully');
    }

    public function test_web_unshare_redirects_back(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'web_unshare.txt', 'path' => 'wu.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $other->files()->attach($file->id, ['permission' => 'viewer']);

        $response = $this->actingAs($owner)
            ->delete("/files/{$file->id}/share/{$other->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Access revoked successfully!');
    }

    public function test_viewer_cannot_share(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $file = File::create(['original_name' => 'no_share.txt', 'path' => 'ns.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $viewer->files()->attach($file->id, ['permission' => 'viewer']);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/files/{$file->id}/share", [
                'user_id' => $other->id,
                'permission' => 'editor',
            ]);

        $response->assertStatus(403);
    }

    public function test_soft_deleted_file_returns_404(): void
    {
        $owner = User::factory()->create();
        $file = File::create(['original_name' => 'gone.txt', 'path' => 'gone.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $file->delete();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/files/{$file->id}");

        $response->assertStatus(404);
    }

    public function test_editor_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $file = File::create(['original_name' => 'editor_no_delete.txt', 'path' => 'end.txt', 'mime_type' => 'text/plain', 'size' => 10]);
        $owner->files()->attach($file->id, ['permission' => 'owner']);
        $editor->files()->attach($file->id, ['permission' => 'editor']);

        $response = $this->actingAs($editor, 'sanctum')
            ->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(403);
    }

    public function test_owner_can_edit_file(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'edit_inline.txt',
            'path' => 'uploads/edit_inline.txt',
            'mime_type' => 'text/plain',
            'size' => 50,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);
        Storage::disk('minio')->put('uploads/edit_inline.txt', 'editable content');

        $response = $this->actingAs($user)
            ->get("/files/{$file->id}/edit");

        $response->assertStatus(200)
            ->assertViewHas('file')
            ->assertViewHas('content')
            ->assertViewHas('extension');
    }

    public function test_edit_returns_404_when_file_missing_on_storage(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'missing.txt',
            'path' => 'uploads/missing.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($user)
            ->get("/files/{$file->id}/edit");

        $response->assertStatus(404);
    }

    public function test_show_downloads_file(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'download.txt',
            'path' => 'uploads/download.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);
        Storage::disk('minio')->put('uploads/download.txt', 'download content');

        $response = $this->actingAs($user)
            ->get("/files/{$file->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('download.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_update_with_web_redirects_back(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'web_update.txt',
            'path' => 'uploads/web_update.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);
        Storage::disk('minio')->put('uploads/web_update.txt', 'original');

        $response = $this->actingAs($user)
            ->put("/files/{$file->id}", ['content' => 'updated']);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File saved successfully!');
    }

    public function test_destroy_with_web_redirects_back(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'destroy_web.txt',
            'path' => 'uploads/destroy_web.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($user)
            ->delete("/files/{$file->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'File deletion initiated!');
    }

    public function test_show_returns_json_for_api(): void
    {
        $user = User::factory()->create();
        $file = File::create([
            'original_name' => 'api_show.txt',
            'path' => 'uploads/api_show.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);
        $user->files()->attach($file->id, ['permission' => 'owner']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJsonPath('original_name', 'api_show.txt');
    }
}

