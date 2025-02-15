<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document; 
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use App\Models\Folder;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Tables\Columns\TextColumn;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

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
