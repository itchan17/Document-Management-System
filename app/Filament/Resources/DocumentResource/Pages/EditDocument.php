<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Folder;
use Carbon\Carbon;
use App\Filament\Pages\UploadDocument;
use Illuminate\Support\Facades\Storage;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Filament\Actions\Action;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    // Convert pdf to text if ever file has been changed
    protected function mutateFormDataBeforeSave(array $data): array
    {    
        
        //Instantiate CreateDocument
        $createDocument = new UploadDocument();

        // extract the file
        $data['file_content'] = ($createDocument->extractContent($data));
        
        // check if file_path is an array which means it is multiple images
        // then run the method that compile those images in pdf
        if (is_array($data['file_path']) && count($data['file_path']) == 1 && $data['file_type'] == 'image'){
            
            $data['file_path'] = $data['file_path'][0];

            $data['file_name'] = $data['file_name'][$data['file_path']];

        }
        elseif (is_array($data['file_path']) && count($data['file_path']) > 1  && $data['file_type'] == 'image') {

            $file_path_arr = $data['file_path'];

            $newData = ($createDocument->convertImagesToPDF($data['file_path'], $data['title']));

            $data['file_path'] =  $newData['file_path'];
            $data['file_name'] =  $newData['file_name'];
            $data['file_type'] =  $newData['file_type'];

            foreach($file_path_arr as $path){
                Storage::disk('local')->delete($path);
            }
        } 
        
        // Add date and time if new document is added in the folder
        $createDocument->updateDateModified($data['folder']);
        
            return $data;  // Return the data to be save in database

      
    }
    
    // check for long file content
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $origRecord = $record;
        
        try {

            $record->update($data);
     
            return $record;

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

    protected function getSaveFormAction(): Action
    {
        return Action::make('customSave')
            ->label('Save')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-information-circle')
            ->modalHeading('Upload Document')
            ->modalDescription('Are you sure you want to save changes?')
            ->action(function () {
                $this->closeActionModal();
                $this->save();
            });
    }
}
