<?php

namespace App\Filament\Resources\FolderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\View;
use App\Models\Document; 
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Smalot\PdfParser\Parser;
use Illuminate\Validation\Rule; 
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Closure;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use App\Models\Folder;
use Carbon\Carbon;
use App\Filament\Pages\CreateDocument;

class GetDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('Upload FIle')
            ->schema([
                FileUpload::make('file_path')
                        ->label('File')                 
                        ->disk('local')
                        ->visibility('private')
                        ->directory('documents')
                        ->storeFileNamesIn('file_name')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',     
                            'image/png',   
                            'image/webp'        
                        ])  
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                // Store the file extension in the form state
                                $set('file_extension', $state->extension());
                            }
                        })              
                        ->rules([
                            function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    if ($value) {                                   
                                        // Get the original file name
                                        $fileName = $value->getClientOriginalName(); 

                                        // Check if the file/file_name already exists
                                        $exists = Document::where('file_name', $fileName)->exists();
                                        
                                        // If exists throw message saying the file already exists
                                        if ($exists) {                                            
                                            $fail("The file '{$fileName}' already exists.");
                                        }
                                    }
                                };
                            },
                            function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    $mimeType = $value->getMimeType();

                                    $acceptedTypes = [
                                        'image/jpeg',      
                                        'image/png',       
                                        'image/webp'       
                                    ];
                                    // check if the field has value and mimetype is in the array
                                    if ($value && in_array($mimeType, $acceptedTypes)) {    
                                        try {
                                            // try to extract text from the file
                                            $text = (new TesseractOCR($value->getRealPath()))->lang('eng')->run();
                                        }  
                                        catch (\Exception $e) {
                                            // send an error thath there's no text that can be extracted
                                            $fail("Can't read the file.");
                                        }                                                            
                                    }
                                };
                            },
                        ]), 
            ])->columnSpan('full'),
            Hidden::make('file_extension'),
            Section::make('Document Details') 
            ->columns(['sm' => 1, 'md' => 3])
            ->schema([
                TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->rules([
                            function (?Model $record) {

                                return function (string $attribute, $value, Closure $fail) use ($record) {
                                   // Check if the new value and previous value is same
                                   $prevValue = ($record && $record->title == $value) ? true : false;

                                    if ($value) {                                          

                                        // Check if the file/file_name already exists
                                        $exists = Document::where('title', $value)->exists();

                                        // If exists throw message saying the file already exists
                                        if ($exists && !$prevValue) {                                            
                                            $fail("The title already exists.");
                                        }
                                    }
                                };
                            },
                        ]),   
                DatePicker::make('file_date')
                    ->label('File Date')
                    ->required(),
                Select::make('folder_id')
                    ->label('Select Folder')  
                    ->relationship('getFolder', 'folder_name')
                    ->hiddenOn('create')
                    ->suffixIcon('heroicon-s-folder'),  
                TextArea::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpan('full')
                    ->rows(3),
                           
            ])->columnSpan('full'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Stack::make([                  
                    TextColumn::make('title')
                        ->weight(FontWeight::Medium)
                        ->size(TextColumn\TextColumnSize::Medium),       
                    TextColumn::make('file_name')
                        ->icon('heroicon-s-document-text')
                        ->iconColor('primary'),
                ]),
                View::make('documents.table.collapsible-row-content')
                    ->collapsible(),     
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 2,
            ])
            ->searchable()
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
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
                        $data['user_id'] = auth()->id(); 

                        

                        // Add date and time if new document is added in the folder
                        $folderId = $livewire->ownerRecord->id;

                        $createDocument = new CreateDocument();

                        $createDocument->updateDateModified($folderId);

                        return $data;  // Return the data to be save in database
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()  
                ->mutateFormDataUsing(function (array $data, $livewire): array {

                    // Add date and time if new document is added in the folder
                    $createDocument = new CreateDocument();

                    $createDocument->updateDateModified($data['folder_id']);

                    return $data;  // Return the data to be save in database
                }),  
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        // Check if there is a search query
        if (filled($search = $this->getTableSearch())) {
            
            // Use MeiliSearch to get the matching document IDs
            $documentIds = Document::search($search)->keys();

            // Apply the search results to the Eloquent query
            $query->whereIn('id', $documentIds);
         
        }
        return $query;
    }
}
