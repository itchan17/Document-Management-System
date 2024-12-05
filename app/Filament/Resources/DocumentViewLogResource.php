<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentViewLogResource\Pages;
use App\Filament\Resources\DocumentViewLogResource\RelationManagers;
use App\Models\DocumentViewLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class DocumentViewLogResource extends Resource
{
    protected static ?string $model = DocumentViewLog::class;

    protected static ?string $navigationIcon = 'heroicon-s-eye';

    protected static ?string $navigationLabel = 'View History';

    protected static ?string $pluralLabel = 'View History';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Admin Tools';

    protected static bool $shouldRegisterNavigation = false;


    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'SUPER ADMIN';
    }

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
            ->columns([

                TextColumn::make('user.name')
                ->label('First Name')
                ->searchable(),

                TextColumn::make('user.lastname')
                ->label('Last Name')
                ->searchable(),

                TextColumn::make('viewed_at')
                ->label('Time and Date')
                ->dateTime('m/d/Y h:i A'),
            ])
            ->filters([
                //
            ])
            ->actions([             

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
            'index' => Pages\ListDocumentViewLogs::route('/'),
            'create' => Pages\CreateDocumentViewLog::route('/create'),
            'edit' => Pages\EditDocumentViewLog::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
