<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileRemoval;
use App\Jobs\CleanupFileJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * List user's files
     */
    public function index(Request $request)
    {
        Log::info('FileController@index called by user: ' . Auth::id());
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // The relationship should automatically filter trashed files, 
        // but we'll ensure we query non-trashed ones.
        if ($request->wantsJson()) {
            $files = $user->files()->whereNull('files.deleted_at')->with('users')->paginate(15);
            return response()->json($files);
        }

        $allFiles = $user->files()->whereNull('files.deleted_at')->with('users')->get();
        
        $myFiles = $allFiles->filter(function ($file) {
            return $file->pivot->permission === 'owner';
        });

        $sharedFiles = $allFiles->filter(function ($file) {
            return $file->pivot->permission !== 'owner';
        });

        $editableFiles = $allFiles->filter(function ($file) {
            return in_array($file->pivot->permission, ['owner', 'editor']);
        });

        $viewOnlyFiles = $allFiles->filter(function ($file) {
            return $file->pivot->permission === 'viewer';
        });

        $users = \App\Models\User::where('id', '!=', $user->id)->get();

        return view('files.index', compact('myFiles', 'sharedFiles', 'editableFiles', 'viewOnlyFiles', 'users'));
    }

    /**
     * Upload a file
     */
    public function store(Request $request)
    {
        Log::info('FileController@store called by user: ' . Auth::id());
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $uploadedFile = $request->file('file');
        Log::info("Uploading file: " . $uploadedFile->getClientOriginalName());
        $datePath = now()->format('Y-m-d');
        
        $path = $uploadedFile->storeAs(
            "file-upload/{$datePath}", 
            $uploadedFile->hashName(), 
            'minio'
        );

        if (!$path) {
            Log::error('Failed to store file on Minio disk.');
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
     * Edit file in browser
     */
    public function edit(File $file)
    {
        Log::info("FileController@edit called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file, ['owner', 'editor']);

        if (!Storage::disk($file->disk)->exists($file->path)) {
            Log::warning("File not found on storage: {$file->path}");
            abort(404, 'File not found on storage.');
        }

        $content = Storage::disk($file->disk)->get($file->path);
        $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);

        return view('files.edit', compact('file', 'content', 'extension'));
    }

    /**
     * Update file content
     */
    public function update(Request $request, File $file)
    {
        Log::info("FileController@update called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file, ['owner', 'editor']);

        $request->validate([
            'content' => 'required|string',
        ]);

        // Store old path for cleanup
        $oldPath = $file->path;
        $datePath = now()->format('Y-m-d');
        $newPath = "file-upload/{$datePath}/" . Str::random(40);
        Log::info("Updating file ID {$file->id}. Moving from {$oldPath} to {$newPath}");
        
        // Save new content to a new path
        Storage::disk($file->disk)->put($newPath, $request->input('content'));

        // Update file model with new path
        $file->update(['path' => $newPath]);

        // Create removal record for old file version
        $fileRemoval = FileRemoval::create([
            'file_id' => $file->id,
            'disk' => $file->disk,
            'old_path' => $oldPath,
            'status' => FileRemoval::STATUS_PENDING,
        ]);
        Log::info("Created FileRemoval record ID: {$fileRemoval->id} for path: {$oldPath}");

        // Dispatch cleanup job
        CleanupFileJob::dispatch($fileRemoval);
        Log::info("Dispatched CleanupFileJob for FileRemoval ID: {$fileRemoval->id}");

        if ($request->wantsJson()) {
            return response()->json(['message' => 'File saved successfully']);
        }

        return redirect()->back()->with('success', 'File saved successfully!');
    }

    /**
     * Get file details
     */
    public function show(File $file)
    {
        Log::info("FileController@show called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file);

        if (request()->expectsJson()) {
            return response()->json($file);
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            Log::warning("File not found on storage: {$file->path}");
            abort(404, 'File not found on storage.');
        }

        // For non-local disks, stream the file as a response
        $stream = Storage::disk($file->disk)->readStream($file->path);
        if (!$stream) {
            Log::error("Failed to read stream for file: {$file->path}");
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
        Log::info("FileController@destroy called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file, 'owner');

        // Create removal record
        $fileRemoval = FileRemoval::create([
            'file_id' => $file->id,
            'disk' => $file->disk,
            'old_path' => $file->path,
            'status' => FileRemoval::STATUS_PENDING,
        ]);
        Log::info("Created FileRemoval record ID: {$fileRemoval->id} for path: {$file->path}");

        // Delete the file record (FileRemoval record will persist with file_id = null due to nullOnDelete)
        $file->delete();
        Log::info("Deleted File record ID: {$file->id}");

        // Dispatch cleanup job
        CleanupFileJob::dispatch($fileRemoval);
        Log::info("Dispatched CleanupFileJob for FileRemoval ID: {$fileRemoval->id}");

        if ($request->wantsJson()) {
            return response()->json(['message' => 'File deletion initiated']);
        }

        return redirect()->back()->with('success', 'File deletion initiated!');
    }

    /**
     * Share a file with another user
     */
    public function share(Request $request, File $file)
    {
        Log::info("FileController@share called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file, 'owner');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:editor,viewer,none',
        ]);

        if ($request->permission === 'none') {
            $file->users()->detach($request->user_id);
            Log::info("Revoked access for user {$request->user_id} on file ID {$file->id}");
            $message = 'Access revoked successfully';
        } else {
            $file->users()->syncWithoutDetaching([
                $request->user_id => ['permission' => $request->permission]
            ]);
            Log::info("Shared file ID {$file->id} with user {$request->user_id} with permission {$request->permission}");
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
        Log::info("FileController@unshare called for file ID: {$file->id} by user: " . Auth::id());
        $this->authorizeAccess($file, 'owner');

        $file->users()->detach($user->id);
        Log::info("Revoked access for user {$user->id} on file ID {$file->id}");

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
        // Check if the file is soft-deleted
        if ($file->trashed()) {
            Log::warning("Access attempt for soft-deleted file ID {$file->id} by user " . Auth::id());
            abort(404, 'File not found.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userFile = $user->files()->where('file_id', $file->id)->first();

        if (!$userFile) {
            Log::warning("Unauthorized access attempt for file ID {$file->id} by user " . Auth::id());
            abort(403, 'Unauthorized access to this file.');
        }

        if ($requiredPermission) {
            $permissions = is_array($requiredPermission) ? $requiredPermission : [$requiredPermission];
            if (!in_array($userFile->pivot->permission, $permissions)) {
                Log::warning("Forbidden access attempt for file ID {$file->id} by user " . Auth::id() . ". Required: " . implode(',', $permissions));
                abort(403, 'You do not have the required permissions.');
            }
        }
    }
}