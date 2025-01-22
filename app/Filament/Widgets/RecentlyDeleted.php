<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use App\Models\ActivityLog;
use App\Models\Document;
use Spatie\Activitylog\Models\Activity;

class RecentlyDeleted extends BaseWidget
{

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {          
        return $table
            ->query(
                Activity::where('event', 'deleted')
                    ->latest()               
                    ->limit(3)                                             
            )
            ->columns([
                TextColumn::make('subject_title')
                    ->label('Title')
                    ->wrap(),
                TextColumn::make('subject_file_name')
                    ->label('File name')
                    ->wrap(),

            ])
            ->paginated(false); 
    }
}
