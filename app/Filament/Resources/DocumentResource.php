<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Filament\Resources\DocumentResource\Pages\ListDocumentActivities;
use App\Models\Document;
use App\Models\User;
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
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\Alignment;
 
set_time_limit(3600);  //Set execution time to 1hr

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
                Section::make('Upload File')
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
                        ->maxSize(12 * 1024)
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
                        ->label('File date'),
                    Select::make('folder')
                        ->label('Select folder')  
                        ->options(Folder::all()->pluck('folder_name', 'id'))
                        ->hiddenOn('create')
                        ->suffixIcon('heroicon-s-folder'),
                    TextArea::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpan('full')
                        ->rows(3),
                               
                ])
                ->columnSpan('full'),
                
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
                    TextColumn::make('created_at')
                        ->size(TextColumn\TextColumnSize::Small)
                        ->dateTime('F j, Y, g:i a'),   
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
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_at')
                            ->label('Uploaded at')  ,
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['created_at']) {
                            return null;
                        }
                 
                        return 'Uploaded at ' . Carbon::parse($data['created_at'])->toFormattedDateString();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_at'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '=', $date),
                            );
                    }),
                Filter::make('user_id')
                    ->form([
                        Select::make('user_id')  // Ensure the field matches the filter name
                            ->label('Uploaded by')  
                            ->options(User::all()->mapWithKeys(function ($user) {
                                return [$user->id => $user->name . ' ' . $user->lastname];
                            })->toArray()), 
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['user_id']) {
                            return null;
                        }
                        $user = User::find($data['user_id']);
                        return 'Uploaded by ' . $user->name . ' ' . $user->lastname;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['user_id']),  // Check if the value is not empty to avoid errors
                            fn (Builder $query) => $query->where('user_id', '=', $data['user_id'])
                        );
                    }),
                Filter::make('file_date')
                    ->form([
                        DatePicker::make('file_date')
                            ->label('File date')  ,
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['file_date']) {
                            return null;
                        }
                 
                        return 'File date ' . Carbon::parse($data['file_date'])->toFormattedDateString();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['file_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('file_date', '=', $date),
                            );
                    }),                                     
            ])
            ->actions([
                 Action::make('viewFile') // view function
                ->label('View')
                ->color('gray')
                ->icon('heroicon-s-eye')
                ->modalIconColor('danger')
                ->modalIcon('heroicon-s-eye')
                ->modalHeading(function (Document $document){                 
                    return $document->title;
                })
                ->modalContent(function (Document $document): HtmlString {
                    
                    if (auth()->check()) {
                        DocumentViewLog::create([
                            'document_id' => $document->id,
                            'user_id' => auth()->id(),
                            'viewed_at' => now(),
                        ]);
                    }
                    if (Storage::disk('local')->exists($document->file_path)) {
                        return new HtmlString(
                            '<iframe src="' . route('documents.view', [$document->id]) . '" width="100%" height="600px"></iframe>'
                        );
                    }else{
                        return new HtmlString(
                            '<center><p>File not found.</p><center>'
                        );
                    }
                })
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->modalCancelAction(fn (StaticAction $action) => $action->label('Close')) 
                ->modalFooterActionsAlignment(Alignment::Center)
                ->modalSubmitAction(false)
                ->extraModalFooterActions([
                    Action::make('ViewMore')
                        ->label('View In New Tab')
                        ->color('primary')
                        ->url(function (Document $document) {
                            return route('documents.view', [$document->id]);
                        })
                        ->openUrlInNewTab()
                        ->disabled(function (Document $document) {
                            return !Storage::disk('local')->exists($document->file_path);
                        }),
                ]),






                
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
