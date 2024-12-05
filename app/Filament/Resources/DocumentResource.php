<?php

namespace App\Filament\Resources;


use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Filament\Resources\DocumentResource\Pages\ListDocumentActivities;
use App\Models\Document;
use App\Models\Folder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Smalot\PdfParser\Parser;
use Illuminate\Validation\Rule; 
use Closure;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\CreateAction;
use Filament\Actions\StaticAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Exception;
use App\Models\DocumentViewLog;
use Illuminate\Support\Facades\DB;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-s-document';

    protected static ?string $navigationLabel = 'Documents';

    protected ?string $heading = 'Upload Document';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 4;

    // protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
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
                        ->suffixIcon('heroicon-s-folder'),
                    TextArea::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpan('full')
                        ->rows(3),
                               
                ])->columnSpan('full'),
            ]);
    }
 
    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) 
            ->columns([
                Stack::make([                  
                    TextColumn::make('title')
                        ->weight(FontWeight::Medium)
                        ->size(TextColumn\TextColumnSize::Medium),       
                    TextColumn::make('file_name')
                        ->icon('heroicon-s-document')
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
            ->defaultSort('created_at', 'desc')
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
                

                Tables\Actions\EditAction::make()
                    ->color('gray'),               
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
            ])
            ->paginated([10, 20, 50, 100, 'all']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
