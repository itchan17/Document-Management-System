<?php

namespace App\Filament\Resources\DocumentViewLogResource\Pages;

use App\Filament\Resources\DocumentViewLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;


class ListDocumentViewLogs extends ListRecords
{
    protected static string $resource = DocumentViewLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder

    {
        $query = parent::getTableQuery();

        $recordId = request()->route('record');
    
        if ($recordId) {
            $query->where('document_id', $recordId);
        }
    
        return $query;
    }
    
}
