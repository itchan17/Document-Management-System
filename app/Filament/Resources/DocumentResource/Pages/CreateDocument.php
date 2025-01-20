<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\FolderResource;
use Filament\Resources\Pages\CreateRecord;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Folder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Filament\Actions\Action;
use App\Filament\Pages\UploadDocument;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected static bool $canCreateAnother = false;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected static ?string $navigationLabel = 'Custom Navigation Label';

    protected static ?string $title = 'Upload Document';
    
    // Add this property to store the folder ID
    public ?int $folderId = null;

    // Mount method to set the folder ID when the page loads
    public function mount(): void
    {   
        parent::mount();
        
        // Get the owner record if it exists
        if (request()->get('ownerRecord')) {
            $this->folderId = request()->get('ownerRecord');
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {    
        //Instantiate CreateDocument
        $createDocument = new UploadDocument();
     
        // extract the file
        $data['file_content'] = ($createDocument->extractContent($data));
        
        // check if file_path is an array which means it is multiple images
        if (is_array($data['file_path']) && count($data['file_path']) == 1 && $data['file_type'] == 'image') {
            $data['file_path'] = $data['file_path'][0];
            $data['file_name'] = $data['file_name'][$data['file_path']];
        }
        elseif (is_array($data['file_path']) && count($data['file_path']) > 1 && $data['file_type'] == 'image') {
            $file_path_arr = $data['file_path'];
            
            $newData = ($createDocument->convertImagesToPDF($data['file_path'], $data['title']));
            
            $data['file_path'] = $newData['file_path'];
            $data['file_name'] = $newData['file_name'];
            $data['file_type'] = $newData['file_type'];
            
            foreach($file_path_arr as $path) {
                Storage::disk('local')->delete($path);
            }
        } 
        
        $data['user_id'] = auth()->id();
        // Add the folder ID to the data

        if ($this->folderId) {
            $folder = Folder::find($this->folderId);
            if ($folder) {
                $data['folder'] = $this->folderId;
                $createDocument->updateDateModified($this->folderId);
            }
            else{
                $this->folderId = null;
            }
        }

        return $data;
    }

    // check for long file content
    protected function handleRecordCreation(array $data): Model
    {
        if(!$this->folderId) {
            Notification::make()
            ->danger()
            ->title('Cannot find folder')
            ->send();

            // Delete the uploaded file if there's an error
            if (Storage::exists($data['file_path'])) {

                Storage::delete($data['file_path']);
            }
        
            // Clear the file input in the form (if applicable)
            $this->data['file_path'] = null;

            $this->halt();
        }
        try {
       
            return static::getModel()::create($data);

        } catch (QueryException $e) {
        
            // Delete the uploaded file if there's an error
            if (Storage::exists($data['file_path'])) {

                Storage::delete($data['file_path']);
            }
        
            // Clear the file input in the form (if applicable)
            $this->data['file_path'] = null;

            Notification::make()
                ->danger()
                ->title('File content is too long')
                ->send();

            $this->halt();
        }
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('upload')
            ->label('Upload')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-information-circle')
            ->modalHeading('Upload Document')
            ->modalDescription('Are you sure you want to upload this document?')
            ->action(function () {
                $this->closeActionModal();
                $this->create();
            });
    }

    // Override getRedirectUrl to return to the folder view
    protected function getRedirectUrl(): string
    {
        return FolderResource::getUrl('view', ['record' => $this->folderId]);
    }
}