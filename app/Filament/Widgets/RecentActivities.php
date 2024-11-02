<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\ActivityLog;

class RecentActivities extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('event')
                    ->label('Activity')
                    ->formatStateUsing(fn ($state) => ucwords($state)),

                TextColumn::make('document.title')
                ->label('Document'),

                TextColumn::make('created_at')
                    ->label('Date and Time')
                    ->dateTime('F j, Y, g:i a'),
    
                TextColumn::make('user.name')
                    ->label('User'),
            ])
            ->paginated(false); 
    }
}
