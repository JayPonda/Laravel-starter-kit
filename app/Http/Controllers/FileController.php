<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    /**
     * List user's files
     */
    public function index()
    {
        $files = Auth::user()->files()->paginate(15);
        return response()->json($files);
    }

    /**
     * Upload a file
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('uploads', 'minio');

        $file = File::create([
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'disk' => 'minio',
        ]);

        // Attach to user as owner
        Auth::user()->files()->attach($file->id, ['permission' => 'owner']);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $file
        ], 201);
    }

    /**
     * Get file details
     */
    public function show(File $file)
    {
        $this->authorizeAccess($file);
        return response()->json($file);
    }

    /**
     * Delete a file
     */
    public function destroy(File $file)
    {
        $this->authorizeAccess($file, 'owner');

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }

    /**
     * Helper to check permissions
     */
    protected function authorizeAccess(File $file, $requiredPermission = null)
    {
        $userFile = Auth::user()->files()->where('file_id', $file->id)->first();

        if (!$userFile) {
            abort(403, 'Unauthorized access to this file.');
        }

        if ($requiredPermission && $userFile->pivot->permission !== $requiredPermission) {
            abort(403, 'You do not have the required permissions.');
        }
    }
}
EOD;
