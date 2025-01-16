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
use Filament\Tables\Actions\Action;
use App\Models\DocumentViewLog;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\CreateAction;

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
                        ->maxSize(12 * 1024)
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
                                        $fail("No readable text found in '{$fileName}'.");

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
                        ->maxSize(12 * 1024)
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
                                    $fileName = $value->getClientOriginalName(); 
                            
                                    if ($value) {  
                                        try {
                                            $filePath = $value->getRealPath();
                                                                                                            
                                            // Second check: Try a basic PDF header check
                                            $handle = fopen($filePath, 'rb');
                                            if ($handle) {
                                                $header = fread($handle, 4);
                                                fclose($handle);
                                                if ($header !== '%PDF') {
                                                    $fail("The file '{$fileName}' appears to be corrupted.");
                                                    return;
                                                }
                                            }

                                            // Try parsing with PDFParser
                                            try {
                                                $parser = new Parser();
                                                
                                                // Use a different approach to extract text
                                                $pdf = @$parser->parseFile($filePath);
                                                
                                                // If parsing succeeded, try to extract pages and text
                                                if ($pdf) {
                                                    $pages = $pdf->getPages();
                                                    $text = '';
                                                    
                                                    // Extract text page by page
                                                    foreach ($pages as $page) {
                                                        try {
                                                            $text .= $page->getText() . "\n";
                                                        } catch (\Throwable $e) {
                                                            continue; // Skip problematic pages
                                                        }
                                                    }
                                                    
                                                    if (empty(trim($text))) {
                                                        $fail("No readable text found in '{$fileName}'.");
                                                        return;
                                                    }
                                               
                                                } else {
                                                    $fail("Unable to parse '{$fileName}'. The file might be corrupted or encrypted.");
                                                    return;
                                                }
                                                
                                            } catch (\Exception $e) {
                                                \Log::error("PDF Parse Error for {$fileName}: " . $e->getMessage());
                                                $fail("Unable to process '{$fileName}'. Please ensure it's a valid PDF document.");
                                                return;
                                            }
                                            
                                        } catch (\Exception $e) {
                                            \Log::error("File Processing Error: " . $e->getMessage());
                                            $fail("An error occurred while processing the file. Please try again.");
                                            return;
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
                CreateAction::make()
                    ->createAnother(false)
                    ->successNotification(
                        Notification::make()
                              ->success()
                              ->title('Document saved')
                    )
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
                    })
                    ->using(function (CreateAction $action, array $data, string $model, $livewire): Model {        
                        try{

                            // save the folder id
                            $data['folder'] = $this->ownerRecord->id;

        

                            // Save the data to the database
                            return $model::create($data);

                        
                        }
                        catch(QueryException $e){ 
            
                            // Will check if the file content is too long
            
                            Notification::make()
                            ->danger()
                            ->title('File content is too long')
                            ->send();
            
                            // Delete thefile in file system
                            if (Storage::disk('local')->exists($data['file_path'])) {
            
                                // delete the file in the database
                                Storage::disk('local')->delete($data['file_path']);
            
                            }

                            $livewire->mountedTableActionsData[0]['file_path'] = null;

                            $action->halt();
                            
                        }    
                    }),
            ])
            ->actions([
                Action::make('viewFile') // view function
                ->label('View')
                ->color('gray')
                ->icon('heroicon-s-eye')
                ->url(fn (Document $record): string => '')
                ->before(function (Document $record) {
                    // Log the document view action 
                    if (auth()->check()) {
                        DocumentViewLog::create([
                            'document_id' => $record->id,
                            'user_id' => auth()->id(),
                            'viewed_at' => now(),
                        ]);
                    }
                })
                ->requiresConfirmation()
                ->modalIcon('heroicon-s-eye')
                ->modalHeading('Confirm')
                ->modalDescription('This document contains confidential information. Are you sure you want to view this document?')
                ->modalSubmitActionLabel('View')
                ->action(function (Document $record) {
                    
                    if (Storage::disk('local')->exists($record->file_path)) {
                        return redirect()->route('documents.view', $record->id);
                    } else {
                        // Handle the case where the file does not exist
                        Notification::make()
                            ->title('File not found')
                            ->danger()
                            ->send();
                    }
                }),

                EditAction::make()  
                    ->mutateFormDataUsing(function (array $data, $livewire): array {


                        // Add date and time if new document is added in the folder
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

                        return $data;  // Return the data to be save in database
                    })
                    ->using(function (EditAction $action, Model $record, array $data, $livewire): Model {

                        try {

                            $record->update($data);
                           
                            return $record;
                
                        } catch (QueryException $e) {
                            // Delete the uploaded file if there's an error
                            if (Storage::exists($data['file_path'])) {
                
                                Storage::delete($data['file_path']);
                            }
                        
                            // Clear the file input in the form (if applicable)
                            $livewire->mountedTableActionsData[0]['file_path'] = null;

                            Notification::make()
                                ->danger()
                                ->title('File content is too long')
                                ->send();
             
                            $action->halt();
                        }
                    }),  
                DeleteAction::make()
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
