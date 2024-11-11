<?php

namespace App\Filament\Resources;
use App\Filament\Resources\DocumentResource\Pages\ListDocumentActivities;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletes; 

class ActivityLogResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-s-clock';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $pluralLabel = 'Activity Log';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Admin Tools';



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
            ->query(Document::query()->withTrashed()) //show deleted files also
            ->columns([
                TextColumn::make('title')
                ->searchable(),

                TextColumn::make('file_type')
                ->label('File Type')
                ->searchable(),

                TextColumn::make('created_at')
                ->label('Created At')
                ->date(),

                TextColumn::make('updated_at')
                ->label('Updated At')
                ->date(),

                TextColumn::make('deleted_at') 
                ->label('Status')
                ->default('Active') // Default state is 'Active kasi di nagana if null ang detected
                ->formatStateUsing(fn ($record) => $record->deleted_at !== null ? 'Deleted' : 'Active') 
                ->color(fn ($record) => $record->deleted_at !== null ? 'danger' : 'success') // Sets color to 
                ->searchable(false)
                
                

            ])
            
            ->filters([
                //
            ])
            ->actions([

                Action::make('activities')
                ->url(fn ($record) => static::getUrl('activities', ['record' => $record->id]))
                ->label('View Activities'),

            ])
            ->bulkActions([

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
            'index' => Pages\ListActivityLogs::route('/'),
            'create' => Pages\CreateActivityLog::route('/create'),
            'edit' => Pages\EditActivityLog::route('/{record}/edit'),
            'activities' => DocumentResource\Pages\ListDocumentActivities::route('/{record}/activities'), 
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }



    
}
