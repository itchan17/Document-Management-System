<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class RecentActivities extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest()->limit(5))
            ->columns([

                TextColumn::make('subject_title')
                ->label('Document')
                ->wrap(),

                TextColumn::make('created_at')
                    ->label('Date and time')
                    ->dateTime('F j, Y, g:i a'),
    
                TextColumn::make('causer_id')
                    ->label('User')
                    ->formatStateUsing(function ($state) {
                        
                        $user = User::where('id', $state)->first();

                        if(!is_null($user)){
                            return $user->name;
                        }
                        else{
                            return 'Deleted User';
                        }

                    }),
                    
                TextColumn::make('event')
                    ->label('Activity')
                    ->color(fn($state) => $state == 'deleted' ? 'danger' : 
                    ($state == 'created' ? 'success' :  
                    ($state == 'updated' ? 'info' : 
                    ($state == 'restored' ? 'warning' : ' '))))
                    ->formatStateUsing(fn ($state) => ucwords($state)),
            ])
            ->paginated(false); 
    }
}
