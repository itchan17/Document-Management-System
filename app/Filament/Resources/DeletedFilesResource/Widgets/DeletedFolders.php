<?php

namespace App\Filament\Resources\DeletedFilesResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Folder;
use Filament\Tables\Columns\TextColumn;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\DeletedFilesResource;
use App\Models\User;

class DeletedFolders extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Folder::query()->onlyTrashed())
            ->paginated(function (){
                
                // dynamically display the pagination
                return Folder::onlyTrashed()->exists();

            })
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No Deleted Folders')
            ->columns([
                TextColumn::make('folder_name')
                    ->icon('heroicon-s-folder')
                    ->searchable(),

                TextColumn::make('deletedBy.name')
                    ->label('Deleted by')
                    ->searchable()
                    ->default('Deleted User'),

                TextColumn::make('deleted_at')
                    ->sortable()
                    ->label('Deleted at')
                    ->dateTime('F j, Y, g:i a'),
            ])

            ->actions([

                Tables\Actions\RestoreAction::make()
                    ->after(function (Folder $folder) {

                        // code for sending database notification
                        $prompt = "The folder '" . $folder->folder_name . "' has been restored by " . auth()->user()->name . '.';
                        $resource = new DeletedFilesResource();
                        $resource->notifyUsers($prompt);

                        $folder->documents()->withTrashed()->each(function ($document) {
                            // Restore the soft-deleted document if it was deleted
                            if ($document->trashed()) {

                                // Check if the file exists in the archive
                                if (Storage::disk('local')->exists($document->file_path)) {
                                    // Define the original file path
                                    $originalPath = 'documents/' . basename($document->file_path);
                                    
                                    // Move the file back to the documents directory
                                    Storage::disk('local')->move($document->file_path, $originalPath);
    
                                    // save the new file path in database
                                    $document->file_path  = 'documents/' . basename($document->file_path);
                                }
                                
                                $document->deleted_through_folder = null;
            
                                $document->restore();

                            }
                            
                        });
                    }),

                Tables\Actions\ForceDeleteAction::make()
                    ->before(function (Folder $folder) {

                        // code for sending database notification
                        $prompt = "The folder '" . $folder->folder_name . "' has been deleted permanently by " . auth()->user()->name . '.';
                        $resource = new DeletedFilesResource();
                        $resource->notifyUsers($prompt);

                        $folder->documents()->withTrashed()->each(function ($document) {

                            if ($document->trashed()){

                                if (Storage::disk('local')->exists($document->file_path)) {
        
                                    // delete the file in the archives
                                    Storage::disk('local')->delete($document->file_path);
        
                                }

                            }

                        });

                    }),

            ]);
    }
}
