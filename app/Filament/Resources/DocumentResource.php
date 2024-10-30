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
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Smalot\PdfParser\Parser;
use Illuminate\Validation\Rule; 
use Closure;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Manage Documents';

    protected ?string $heading = 'Upload Document';

    protected static ?string $navigationGroup = 'Documents';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                function (Document $record) {

                                    return function (string $attribute, $value, Closure $fail) use ($record) {

                                       // Check if the new value and previous value is same
                                       $prevValue = $record->title == $value ? true : false;
   
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
                            ->required(),
                        Select::make('file_type')
                            ->label('File Type')
                            ->options([
                                'contracts' => 'Contracts',
                                'agreements' => 'Agreements',
                            ])
                            ->required(), 
                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255),
                        FileUpload::make('file_path')
                            ->label('Upload File')
                            ->required()
                            ->disk('public')
                            ->directory('documents')
                            ->storeFileNamesIn('attachment_file_names')
                            ->rules([
                                function (Document $record) {
                                    return function (string $attribute, $value, Closure $fail) use ($record) {
                                        $fileName = $value->getClientOriginalName(); 

                                        // Check if the new value and previous value is same
                                        $prevValue = $record->file_name == $fileName ? true : false;

                                        if ($value) {
                                            
                                            // Check if the file/file_name already exists
                                            $exists = Document::where('file_name', $fileName)->exists();

                                            // If exists throw message saying the file already exists
                                            if ($exists && !$prevValue) {                                            
                                                $fail("The file '{$fileName}' already exists.");
                                            }
                                        }
                                    };
                                },
                            ]),       
                        ])
                    ]);
    }
 
    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) 
            ->columns([
                TextColumn::make('title')
                    ->wrap(),
                TextColumn::make('file_type'),
                TextColumn::make('file_date'),
                TextColumn::make('description')
                    ->wrap()
                    ->width('250px'),
            ])->searchable()
            ->actions([
                Action::make('viewFile') //view function
                ->label('View File')
                ->icon('heroicon-o-eye') 
                ->url(fn (Document $record): string => route('documents.view', $record->id)) // Create a URL to the view action
                ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),               
                Tables\Actions\DeleteAction::make()
                    ->after(function (Document $record) {
                        // Check if the file exists
                        if (Storage::disk('public')->exists($record->file_path)) {

                            // Create the new file path with archive directory
                            $newPath = 'archive'.'/'. basename($record->file_path);

                            // Move the file to archive directory
                            Storage::disk('public')->move($record->file_path, $newPath);

                         }
                    }),
            ]);
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
