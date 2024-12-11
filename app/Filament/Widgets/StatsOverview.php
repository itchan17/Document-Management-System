<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Document;
use App\Models\User;
use App\Models\Folder;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', Document::count()),
            Stat::make('Total Folders', Folder::count()),
            Stat::make('Total Administrators', User::count()),
        ];
    }
}
