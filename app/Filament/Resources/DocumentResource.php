<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
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

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Documents';


    public static function form(Form $form): Form
    {
        return $form->schema([
        Grid::make(1)->schema([ //Grid for line by line form inputs
            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255),

            Select::make('file_type') 
                ->label('File Type')
                ->options([ //Combo box of selection ng types
                    'contracts' => 'Contracts',
                    'agreements' => 'Agreements',
                ])
                ->required(),


            FileUpload::make('file_path') 
                ->disk('localUpload')
                ->label('Upload')
                ->required()
                ->acceptedFileTypes(['application/pdf'])
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
