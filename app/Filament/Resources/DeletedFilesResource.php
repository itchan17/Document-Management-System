<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletedFilesResource\Pages;
use App\Filament\Resources\DeletedFilesResource\RelationManagers;
use App\Models\Document;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Section;
use Filament\Notifications\Notification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Carbon\Carbon;

class DeletedFilesResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-c-trash';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Recycle Bin';

    protected static ?string $pluralLabel = 'Recycle Bin';

    protected static ?string $navigationGroup = 'Trash';

    protected ?string $subheading = 'This is the subheading.';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // this query will only display the documents that are deleted independently(not the documents inside deleted folder)
            ->query(Document::onlyTrashed()->where('deleted_through_folder', 0))
            ->heading('Deleted Documents')
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No Deleted Documents')
            ->recordUrl(null) 
            ->columns([
                TextColumn::make('title')
                    ->words(10)
                    ->wrap()
                    ->icon('heroicon-s-document')
                    ->searchable(),

                    TextColumn::make('deletedBy.name')
                    ->label('Deleted by')
                    ->searchable()
                    ->default('Deleted User'),

                TextColumn::make('deleted_at')
                    ->sortable()
                    ->label('Expires In')
                    ->formatStateUsing(function ($state) {
                        // Calculate the number of days left
                        $deletionDate = \Carbon\Carbon::parse($state)->addDays(30);
                        $now = \Carbon\Carbon::now();
                        $daysLeft = abs(round($deletionDate->diffInDays($now)));

                        return "{$daysLeft} " . ($daysLeft != 1 ? 'days' : 'day');

                    }),
            ])

            ->actions([
                Tables\Actions\RestoreAction::make()
                    ->after(function (Document $record) {

                        // code for sending database notification
                        $prompt = "The document '" . $record->title . "' has been restored by " . auth()->user()->name . '.';
                        $resource = new DeletedFilesResource();
                        $resource->notifyUsers($prompt);
                        

                        // Check if the file exists in the archive
                        if (Storage::disk('local')->exists($record->file_path)) {
                            // Define the original file path
                            $originalPath = 'documents/' . basename($record->file_path);
                            
                            // Move the file back to the documents directory
                            Storage::disk('local')->move($record->file_path, $originalPath);

                            // save the new file path in database
                            $record->file_path  = 'documents/' . basename($record->file_path);
                        }

                        // update the value to null
                        $record->deleted_through_folder = null;

                        $record->save();
                    }),

                Tables\Actions\ForceDeleteAction::make() 
                    ->after(function (Document $record) {

                        // code for sending database notification
                        $prompt = "The document '" . $record->title . "' has been deleted permanently by " . auth()->user()->name . '.';
                        $resource = new DeletedFilesResource();
                        $resource->notifyUsers($prompt);
                        
                        if (Storage::disk('local')->exists($record->file_path)) {

                            // delete the file in the database
                            Storage::disk('local')->delete($record->file_path);

                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeletedFiles::route('/'),
            'create' => Pages\CreateDeletedFiles::route('/create'),
            'edit' => Pages\EditDeletedFiles::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Method that notify the users
    public function notifyUsers(string $prompt): void
    {
        // Select all the super admin execpt the user that triggers the notification
        $recipients = User::where('role', 'SUPER ADMIN')->where('id', '!=', auth()->id())->get();

        foreach($recipients as $recipient){

            Notification::make()
                ->info()
                ->title($prompt)
                ->sendToDatabase($recipient);

        }
    }


}
