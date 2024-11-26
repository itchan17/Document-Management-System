<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Folder;
use Carbon\Carbon;
use App\Filament\Pages\CreateDocument;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;
    
    // Convert pdf to text if ever file has been changed
    protected function mutateFormDataBeforeSave(array $data): array
    {    
        //Instantiate CreateDocument
        $createDocument = new CreateDocument();

        $extension = $data['file_extension'];

        $parser = new Parser();
        
        $filePath = $data['file_path'];  

        $extension = $data['file_extension'];  

        $images = [
            'jpg',      
            'png',       
            'webp'       
        ];
        if (in_array($extension, $images)){

            $text = (new TesseractOCR(storage_path('app/private/' . $filePath)))->lang('eng')->run();  // Process the image and extract text

            $data['file_content'] = $text; 
        }   
        elseif($extension == "pdf") {

            $fileContents = $parser->parseFile(storage_path('app/private/' . $filePath))->getText();  // Extract the text

            $data['file_content'] = $fileContents; // Insert the content in the $data array
        }

        // Add date and time if new document is added in the folder
        $createDocument->updateDateModified($data['folder_id']);

        return $data;  // Return the data to be save in database
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
