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
use Exception;
use Illuminate\Support\Facades\Storage;

class GetDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload FIle')
                ->columns([
                    'sm' => 1,
                    'md' => 3,                 
                ])
                ->schema([
                    // Uploading images
                    FileUpload::make('file_path')
                        ->required()
                        ->visibility('private')
                        ->label('Image') 
                        ->disk('local')
                        ->directory('documents')
                        ->storeFileNamesIn('file_name')   
                        ->multiple()
                        ->columnSpan([
                            'sm' => 1,
                            'md' => 2,                 
                        ])
                        ->visible(function (Get $get) {
                            return $get('file_type') == 'image';
                        })
                        ->acceptedFileTypes([
                            'image/jpeg',     
                            'image/png',   
                            'image/webp'        
                        ])
                        ->afterStateHydrated(function ($state, callable $set, Get $get) {

                            // Convert the file names into an array                      
                            if($get('file_type') == 'image'){

                                $set('file_name', is_array($get('file_name')) ? $get('file_name') : [$get('file_path') => $get('file_name')]);
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
                                            $fail("The image '{$fileName}' already exists.");
                                        }
                                    }
                                };
                            },
                            function () {
                                return function (string $attribute, $value, Closure $fail) {

                                    $fileName = $value->getClientOriginalName(); 

                                    try {
                                        // try to extract text from the file
                                        $text = (new TesseractOCR($value->getRealPath()))->lang('eng')->run(); 
                                    }  
                                    catch (Exception $e) {

                                        // send an error thath there's no text that can be extracted
                                        $fail("The image '{$fileName}' contains no readable text.");

                                    }                                                            
                                    
                                };
                            },
                        ]),

                    // Uploading file
                    FileUpload::make('file_path')
                        ->visible(function (Get $get) {
                            return $get('file_type') == 'pdf';
                        })
                        ->columnSpan([
                            'sm' => 1,
                            'md' => 2,                 
                        ])
                        ->required()
                        ->label('File')                 
                        ->disk('local')
                        ->directory('documents')
                        ->storeFileNamesIn('file_name')   
                        ->acceptedFileTypes([
                            'application/pdf',       
                        ])            
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

                                    $parser = New Parser();
                                    $fileName = $value->getClientOriginalName(); 

                                    if ($value) {  
                                        try{
                                            $text = $parser->parseFile($value->getRealPath())->getText();
                                            if(empty($text)) {
                                                $fail("The file '{$fileName}' contains no searchable text.");  
                                            }
                                        }   
                                        catch(Exception $e){
                                            $fail($e->getMessage());
                                        }                                                                   
                                    }
                                };
                            },
                        ]), 
                        Select::make('file_type')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $set('file_path', null);
                            $set('file_name', null);
                        })
                        ->options([
                            'image' => 'Image',
                            'pdf' => 'PDF',
                        ])
                        ->default('pdf')             
                ])->columnSpan('full'),
                Hidden::make('file_extension'),
                Section::make('Document Details')
                ->columns([
                    'sm' => 1,
                    'md' => 3,                 
                ])
                ->schema([
                    TextInput::make('title')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'The :attribute already exists.',
                            ])
                            ->required()
                            ->label('Title')
                            ->maxLength(255)
                            ->rules(['regex:/^[a-zA-Z0-9\s_-]*$/']),
                    DatePicker::make('file_date')
                        ->required()
                        ->label('File Date'),
                    Select::make('folder')
                        ->label('Select Folder')  
                        ->options(Folder::all()->pluck('folder_name', 'id'))
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

                        //Instantiate CreateDocument
                        $createDocument = new CreateDocument();
                        
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
                        $data['user_id'] = auth()->id(); 

                        // Add date and time if new document is added in the folder
                        $folderId = $livewire->ownerRecord->id;

                        $createDocument->updateDateModified($folderId);

                        return $data;  // Return the data to be save in database
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()  
                ->mutateFormDataUsing(function (array $data, $livewire): array {

                    // Add date and time if new document is added in the folder
                    $createDocument = new CreateDocument();

                    $createDocument->updateDateModified($data['folder']);

                    return $data;  // Return the data to be save in database
                }),  
                Tables\Actions\DeleteAction::make()
                    ->after(function (Document $record) {

                        // Check if the file exists
                        if (Storage::disk('local')->exists($record->file_path)) {

                            // Create the new file path with archive directory
                            $newPath = 'archives'.'/'. basename($record->file_path);

                            // Move the file to archive directory
                            Storage::disk('local')->move($record->file_path, $newPath);
                            
                            // save the new file path in the database
                            $record->file_path = 'archives'.'/'. basename($record->file_path);                         
                        }

                        //remove the relation to folder
                        $record->folder = null;

                        // To show in deleted files
                        $record->deleted_through_folder = 0;

                        $record->save();
                    }), 
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
