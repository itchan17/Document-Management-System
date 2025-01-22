<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('customSave')
            ->label('Save')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-information-circle')
            ->modalHeading('Save changes')
            ->modalDescription('Are you sure you want to save changes?')
            ->action(function () {
                $this->closeActionModal();
                $this->save();
            });
    }
}
