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
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->wantsJson()) {
            $files = $user->files()->with('users')->paginate(15);
            return response()->json($files);
        }

        $allFiles = $user->files()->with('users')->get();
        
        $myFiles = $allFiles->filter(function ($file) {
            return $file->pivot->permission === 'owner';
        });

        $sharedFiles = $allFiles->filter(function ($file) {
            return $file->pivot->permission !== 'owner';
        });

        $users = \App\Models\User::where('id', '!=', $user->id)->get();

        return view('files.index', compact('myFiles', 'sharedFiles', 'users'));
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
        $datePath = now()->format('Y-m-d');
        
        $path = $uploadedFile->storeAs(
            "file-upload/{$datePath}", 
            $uploadedFile->hashName(), 
            'minio'
        );

        if (!$path) {
            throw new \Exception('Failed to store file on Minio disk.');
        }

        $file = File::create([
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'disk' => 'minio',
        ]);

        // Attach to user as owner
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->files()->attach($file->id, ['permission' => 'owner']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'File uploaded successfully',
                'file' => $file
            ], 201);
        }

        return redirect()->back()->with('success', 'File uploaded successfully!');
    }

    /**
     * Get file details
     */
    public function show(File $file)
    {
        $this->authorizeAccess($file);

        if (request()->expectsJson()) {
            return response()->json($file);
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'File not found on storage.');
        }

        // For non-local disks, stream the file as a response
        $stream = Storage::disk($file->disk)->readStream($file->path);
        if (!$stream) {
            abort(404, 'File not found on storage.');
        }
        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
        }, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    /**
     * Delete a file
     */
    public function destroy(Request $request, File $file)
    {
        $this->authorizeAccess($file, 'owner');

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'File deleted successfully']);
        }

        return redirect()->back()->with('success', 'File deleted successfully!');
    }

    /**
     * Share a file with another user
     */
    public function share(Request $request, File $file)
    {
        $this->authorizeAccess($file, 'owner');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:editor,viewer,none',
        ]);

        if ($request->permission === 'none') {
            $file->users()->detach($request->user_id);
            $message = 'Access revoked successfully';
        } else {
            $file->users()->syncWithoutDetaching([
                $request->user_id => ['permission' => $request->permission]
            ]);
            $message = 'File shared successfully';
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove access for a specific user
     */
    public function unshare(Request $request, File $file, \App\Models\User $user)
    {
        $this->authorizeAccess($file, 'owner');

        $file->users()->detach($user->id);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Access revoked successfully']);
        }

        return redirect()->back()->with('success', 'Access revoked successfully!');
    }

    /**
     * Helper to check permissions
     */
    protected function authorizeAccess(File $file, $requiredPermission = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userFile = $user->files()->where('file_id', $file->id)->first();

        if (!$userFile) {
            abort(403, 'Unauthorized access to this file.');
        }

        if ($requiredPermission && $userFile->pivot->permission !== $requiredPermission) {
            abort(403, 'You do not have the required permissions.');
        }
    }
}
