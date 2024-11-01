<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Document;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', Document::count()),
            Stat::make('Total Requests', '2'),
            Stat::make('Total Approved Requests', '10'),
        ];
    }
}
