<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\User;

class RecentActivities extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(User::query())
            ->columns([
                TextColumn::make('Activity'),
                TextColumn::make('Date'),
                TextColumn::make('name')
                    ->label('User'),
            ]);
    }
}
