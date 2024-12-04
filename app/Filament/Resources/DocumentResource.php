<?php

namespace App\Filament\Resources;


use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Filament\Resources\DocumentResource\Pages\ListDocumentActivities;
use App\Models\Document;
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
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\CreateAction;
use Filament\Actions\StaticAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
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
            ->columns([
                'sm' => 1,
                'md' => 3,                 
            ])
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
                    ->suffixIcon('heroicon-s-folder'),
                TextArea::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpan('full')
                    ->rows(3),
                           
            ])->columnSpan('full'),
        ])->statePath('data');
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
                         }
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
