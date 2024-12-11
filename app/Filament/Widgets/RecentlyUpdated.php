<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Activity;

class RecentlyUpdated extends BaseWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->where('event', 'updated')
                    ->latest()
                    ->limit(3)
            )
            ->columns([
                TextColumn::make('subject_title')
                ->label('Title'),
                TextColumn::make('subject_file_name')
                    ->label('File name'),
            ])
            ->paginated(false); 
    }
}
