<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CreateDocument extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.create-document';

    protected ?string $heading = 'Upload Document';

    protected static ?string $navigationLabel = 'Upload Document';

    protected static ?string $navigationGroup = 'Documents';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
{
    return $form
        ->schema([
            Grid::make(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('file_date')
                        ->required(),
                    Select::make('file_type')
                        ->label('File Type')
                        ->options([
                            'contracts' => 'Contracts',
                            'agreements' => 'Agreements',
                        ])
                        ->required(), 
                    TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255),
                    FileUpload::make('file_path')
                        ->disk('localUpload')
                        ->label('Upload File')
                        ->acceptedFileTypes(['application/pdf']),       
                ])
        ])
        ->statePath('data');
}


    public function create(): void
    {
        $data = $this->form->getState();

        // Save the data to the database
        Document::create([
            'title' => $data['title'],
            'file_type' => $data['file_type'],
            'file_date' => $data['file_date'],
            'file_path' => $data['file_path'], 
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Document saved!')
            ->send();

        $this->form->fill();
    }

    public function clear(): void
    {
        $this->form->fill();

        Notification::make()
        ->success()
        ->title('Form Cleared!')
        ->send();
    }
}
