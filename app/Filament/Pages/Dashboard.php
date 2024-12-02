<?php
 
namespace App\Filament\Pages;
 
class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-s-home';

    public function getColumns(): int | string | array
    {
        return 3;
    }
}