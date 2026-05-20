<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\FileRemoval;
use App\Jobs\CleanupFileJob;

#[Signature('app:process-pending-removals')]
#[Description('Remove all files from remote storage which are pending in status')]
class ProcessPendingRemovals extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingRemovals = FileRemoval::where('status', FileRemoval::STATUS_PENDING)->get();

        if ($pendingRemovals->isEmpty()) {
            $this->info('No pending file removals found.');
            return;
        }

        $this->info("Processing {$pendingRemovals->count()} pending file removals...");

        $bar = $this->output->createProgressBar($pendingRemovals->count());

        foreach ($pendingRemovals as $removal) {
            CleanupFileJob::dispatch($removal);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Pending file removals have been dispatched to the queue.');
    }
}
