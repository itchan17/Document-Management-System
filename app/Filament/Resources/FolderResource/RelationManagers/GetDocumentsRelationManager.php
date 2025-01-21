<?php

namespace App\Filament\Resources\FolderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\View;
use App\Models\Document; 
use App\Models\User; 
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Smalot\PdfParser\Parser;
use Illuminate\Validation\Rule; 
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Closure;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use App\Models\Folder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use App\Models\DocumentViewLog;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\CreateAction;
use App\Filament\Resources\DocumentResource;
use Filament\Tables\Filters\Filter;

class GetDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) 
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Stack::make([                  
                    TextColumn::make('title')
                        ->weight(FontWeight::Medium)
                        ->size(TextColumn\TextColumnSize::Medium),   
                    TextColumn::make('created_at')
                        ->size(TextColumn\TextColumnSize::Small)
                        ->dateTime('F j, Y, g:i a'),       
                    TextColumn::make('file_name')
                        ->icon('heroicon-s-document-text')
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
            ->headerActions([
                CreateAction::make()
                    ->url(fn ($livewire) => DocumentResource::getUrl('create', ['ownerRecord' => $livewire->ownerRecord->getKey()])),       
            ])
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

                EditAction::make()  
                    ->url(fn (Document $record): string => DocumentResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
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
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        // Check if there is a search query
        if (filled($search = $this->getTableSearch())) {
            
            // Use MeiliSearch to get the matching document IDs
            $documentIds = Document::search($search)->keys();

            // Apply the search results to the Eloquent query
            $query->whereIn('id', $documentIds);
         
        }
        return $query;
    }
}
