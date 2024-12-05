<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletedFilesResource\Pages;
use App\Filament\Resources\DeletedFilesResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Section;


class DeletedFilesResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-c-trash';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Deleted Items';

    protected static ?string $pluralLabel = 'Deleted Items';

    protected static ?string $navigationGroup = 'Trash';

    protected ?string $subheading = 'This is the subheading.';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Deleted Documents')
            ->recordUrl(null) 
            // this quesry will only display the documents that are deleted independently(not the documents inside deleted folder)
            ->query(Document::onlyTrashed()->where('deleted_through_folder', 0))
            ->columns([
                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('deletedBy.name')
                    ->label('Deleted By')
                    ->searchable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('F j, Y, g:i a'),
            ])

          

            ->actions([
                Tables\Actions\RestoreAction::make()
                    ->after(function (Document $record) {
                        // Check if the file exists in the archive
                        if (Storage::disk('local')->exists($record->file_path)) {
                            // Define the original file path
                            $originalPath = 'documents/' . basename($record->file_path);
                            
                            // Move the file back to the documents directory
                            Storage::disk('local')->move($record->file_path, $originalPath);

                            // save the new file path in database
                            $record->file_path  = 'documents/' . basename($record->file_path);
                        }

                        // update the value to null
                        $record->deleted_through_folder = null;

                        $record->save();
                    }),

                Tables\Actions\ForceDeleteAction::make() 
                    ->after(function (Document $record) {
                        
                        if (Storage::disk('local')->exists($record->file_path)) {

                            // delete the file in the database
                            Storage::disk('local')->delete($record->file_path);

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
            'index' => Pages\ListDeletedFiles::route('/'),
            'create' => Pages\CreateDeletedFiles::route('/create'),
            'edit' => Pages\EditDeletedFiles::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }



}
