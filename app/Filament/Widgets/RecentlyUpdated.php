<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\ActivityLog;

class RecentlyUpdated extends BaseWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        // dd(ActivityLog::query()->latest()->limit(3)->get());
        return $table
            ->query(
                ActivityLog::query()
                    ->where('event', 'updated')
                    ->latest()
                    ->limit(3)
            )
            ->columns([
                TextColumn::make('document.title')
                    ->label('Title')
                    ->formatStateUsing(fn ($state) => ucwords($state)),
                TextColumn::make('document.file_name')
                    ->label('File Name'),
            ])
            ->paginated(false); 
    }
}
