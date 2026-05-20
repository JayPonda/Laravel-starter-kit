<?php

namespace App\Jobs;

use App\Models\FileRemoval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class CleanupFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FileRemoval $fileRemoval
    ) {}

    public function handle(): void
    {
        try {
            Storage::disk('minio')->delete($this->fileRemoval->old_path);
            $this->fileRemoval->update(['status' => FileRemoval::STATUS_COMPLETED]);
        } catch (\Throwable $e) {
            $this->fileRemoval->update([
                'status' => FileRemoval::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }
}