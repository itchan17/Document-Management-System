<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\ActivityLog;
use App\Models\Document;


class RecentlyDeleted extends BaseWidget
{

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {          
        return $table
            ->query(
                ActivityLog::query()
                    ->where('event', 'deleted')
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
