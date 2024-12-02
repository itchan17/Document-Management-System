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


class DeletedFilesResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-c-trash';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Deleted Files';

    protected static ?string $pluralLabel = 'Deleted Files';

    protected static ?string $navigationGroup = 'Trash';

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
            ->recordUrl(null) 
            ->columns([
                TextColumn::make('title')
                ->searchable(),

                TextColumn::make('file_type')
                ->label('File Type')
                ->searchable(),

                TextColumn::make('created_at')
                ->label('Created At')
                ->date(),
            ])

            ->filters([
                TrashedFilter::make()
                ->query(fn (Builder $query) => $query->onlyTrashed()), //filter para deleted lang kita
        

            ])
            ->actions([
                Tables\Actions\RestoreAction::make()
                    ->after(function (Document $record) {
                        // Check if the file exists in the archive
                        if (Storage::disk('public')->exists('archive/' . basename($record->file_path))) {
                            // Define the original file path
                            $originalPath = 'documents/' . basename($record->file_path);
    
                            // Move the file back to the documents directory
                            Storage::disk('public')->move('archive/' . basename($record->file_path), $originalPath);
                        }
                    }),

                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
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
