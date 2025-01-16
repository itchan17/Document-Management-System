<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PermanentlyDeleteFiles extends Command
{
    
    protected $signature = 'documents:cleanup';
    protected $description = 'Permanently delete documents that were soft-deleted more than 30 days ago';

    public function handle()
    {
        // Set the grace period to 30 days
        $thresholdDate = Carbon::now()->subDays(30);

        // Get the deleted files that are over 30 days
        $filesToDelete = Document::onlyTrashed()
            ->where('deleted_at', '<', $thresholdDate)
            ->get();

        foreach ($filesToDelete as $file) {

            // Delete the file from storage
            if (Storage::disk('local')->exists($file->file_path)) {

                // Delete the file in the database
                Storage::disk('local')->delete($file->file_path);

            }
            // Permanently delete the database record
            $file->forceDelete();
        }

        $this->info("Deleted {$filesToDelete->count()} files permanently.");
    }
}
