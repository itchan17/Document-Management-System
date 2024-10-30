<?php

namespace App\Filament\Resources\DeletedFilesResource\Pages;

use App\Filament\Resources\DeletedFilesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDeletedFiles extends CreateRecord
{
    protected static string $resource = DeletedFilesResource::class;
}
