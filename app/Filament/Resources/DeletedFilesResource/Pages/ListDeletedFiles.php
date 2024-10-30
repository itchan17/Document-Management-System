<?php

namespace App\Filament\Resources\DeletedFilesResource\Pages;

use App\Filament\Resources\DeletedFilesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeletedFiles extends ListRecords
{
    protected static string $resource = DeletedFilesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
