<?php

namespace App\Filament\Resources\DocumentViewLogResource\Pages;

use App\Filament\Resources\DocumentViewLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentViewLog extends EditRecord
{
    protected static string $resource = DocumentViewLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
