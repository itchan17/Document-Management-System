<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FolderResource\Pages;
use App\Filament\Resources\FolderResource\RelationManagers;
use App\Models\Folder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Storage;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static ?string $navigationIcon = 'heroicon-s-folder';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('folder_name')
                        ->label('Folder Name')
                        ->required()
                        ->unique(table: Folder::class)
                                ->validationMessages([
                                    'unique' => 'The folder name already exists.',
                                ])
                        ->columnSpan('full'), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('folder_name')
                    ->icon('heroicon-s-folder')
                    ->iconColor('primary'),
                TextColumn::make('created_at')
                    ->dateTime('F j, Y, g:i a'),
                TextColumn::make('date_modified')
                    ->dateTime('F j, Y, g:i a')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make() 
                    ->after(function (Folder $folder) {
                     
                        if (auth()->check()) {

                            $folder->deleted_by = auth()->id(); // Get user that deleted the folder
            
                            $folder->save();
            
                            // apply softdelete to each child
                            $folder->documents()->each(function ($document) {

                                if (is_null($document->deleted_through_folder)) {
                                   
                                    // Check if the file exists
                                    if (Storage::disk('local')->exists($document->file_path)) {

                                        // Create the new file path with archive directory
                                        $newPath = 'archives'.'/'. basename($document->file_path);

                                        // Move the file to archive directory
                                        Storage::disk('local')->move($document->file_path, $newPath);
                                        
                                        // save the new file path in the database
                                        $document->file_path = 'archives'.'/'. basename($document->file_path);                         
                                    }
            
                                    // document inside will be hidden in the deleted files to recover the files you need to restore the folder
                                    $document->deleted_through_folder = 1;
            
                                    $document->delete();
                                }
                               
                                
                            });
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GetDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFolders::route('/'),
            'view' => Pages\ViewDocuments::route('/{record}'),
        ];
    }
}
