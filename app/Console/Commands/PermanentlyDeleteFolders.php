<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Folder; 
use App\Models\Document; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PermanentlyDeleteFolders extends Command
{
    
    protected $signature = 'folders:cleanup';
    protected $description = 'Permanently delete folders and documents inside that were soft-deleted more than 30 days ago';

    public function handle()
    {
        // Set the grace period to 30 days
        $thresholdDate = Carbon::now()->subDays(30);

        // Get the deleted folders that are over 30 days
        $foldersToDelete = Folder::onlyTrashed()
            ->where('deleted_at', '<', $thresholdDate)
            ->get();

        
        foreach ($foldersToDelete as $folder) {
            // Get the deleted files inside the folder
            $filesToDelete = $folder->documents()->withTrashed()->get();

            // delete each file from the storage
            foreach ($filesToDelete as $file) {

                // Delete the file from storage
                if (Storage::disk('local')->exists($file->file_path)) {

                    // Delete the file in the database
                    Storage::disk('local')->delete($file->file_path);

                }
                // Permanently delete the database record
                $file->forceDelete();
            }
            // Permanently delete the database record
            $folder->forceDelete();
        }

        $this->info("Deleted {$foldersToDelete->count()} folders permanently.");
    }
}
