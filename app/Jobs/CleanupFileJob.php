<?php

namespace App\Jobs;

use App\Models\FileRemoval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FileRemoval $fileRemoval
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Starting CleanupFileJob for FileRemoval ID: {$this->fileRemoval->id}");
            Storage::disk($this->fileRemoval->disk)->delete($this->fileRemoval->old_path);
            $this->fileRemoval->update(['status' => FileRemoval::STATUS_COMPLETED]);
            Log::info("Successfully completed CleanupFileJob for FileRemoval ID: {$this->fileRemoval->id}");
        } catch (\Throwable $e) {
            Log::error("Failed CleanupFileJob for FileRemoval ID: {$this->fileRemoval->id}. Error: {$e->getMessage()}");
            $this->fileRemoval->update([
                'status' => FileRemoval::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }
}