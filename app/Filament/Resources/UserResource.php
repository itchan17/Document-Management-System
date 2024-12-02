<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationGroup = 'Admin Tools';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Account')
            ->columns([
                'sm' => 1,
                'md' => 3,                 
            ])
            ->schema([
                TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                TextInput::make('email')
                        ->label('Email Address')
                        ->required()
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
        
                TextInput::make('password')
                        ->label('Password')
                        ->required()
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')               
                        ->maxLength(255),

                Select::make('role')
                        ->options(User::ROLES)
                        ->required(),

            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->searchable(),

                TextColumn::make('email')
                ->searchable(),

                TextColumn::make('role')
                ->searchable(),

                TextColumn::make('created_at')
                ->label('Created At')
                ->date(),

                TextColumn::make('updated_at')
                ->label('Updated At')
                ->date(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
