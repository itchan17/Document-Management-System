<?php

namespace App\Filament\Resources\FolderResource\Pages;

use App\Filament\Resources\FolderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Carbon\Carbon;

class ManageFolders extends ManageRecords
{
    protected static string $resource = FolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $date_modified = Carbon::now(config('app.timezone'))->format('Y-m-d H:i:s');
                    
                    $data['date_modified'] = $date_modified;
                    $data['created_by'] = auth()->id();
                    
                    return $data;
                }),
        ];
    }
}
