<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Smalot\PdfParser\Parser;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    // Convert pdf to text if ever file has been changed
    protected function mutateFormDataBeforeSave(array $data): array
    {        
        $parser = new Parser();

        $filePath = $data['file_path'];   
 
        $fileContents = $parser->parseFile(storage_path('app/public/' . $filePath))->getText(); // Extract the text
    
        $data['file_content'] = $fileContents; // Insert the content in the $data array
  
        return $data;  // Return the data to be save in database
    }
}
