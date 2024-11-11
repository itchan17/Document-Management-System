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
use Smalot\PdfParser\Parser;
use Illuminate\Validation\Rule; 
use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\RichEditor;


class CreateDocument extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-s-document-text';

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
                Section::make('Document Details')
                ->columns([
                    'sm' => 1,
                    'md' => 3,                 
                ])
                ->schema([
                    TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: Document::class)
                                ->validationMessages([
                                    'unique' => 'The :attribute already exists.',
                                ]),
                    DatePicker::make('file_date')
                        ->label('File Date')
                        ->required(),
                    Select::make('file_type')
                        ->label('File Type')
                        ->options([
                            'contracts' => 'Contracts',
                            'agreements' => 'Agreements',
                        ])
                        ->required(), 
                    TextArea::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpan('full')
                        ->rows(3),
                               
                ])->columnSpan('full'),
                Section::make('Upload FIle')
                ->schema([
                    FileUpload::make('file_path')
                            ->label('File')
                            ->required()
                            ->disk('public')
                            ->directory('documents')
                            ->storeFileNamesIn('file_name')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, Closure $fail) {
                                        if ($value) {
                                            // Get the original file name
                                            $fileName = $value->getClientOriginalName(); 

                                            // Check if the file/file_name already exists
                                            $exists = Document::where('file_name', $fileName)->exists();
                                            
                                            // If exists throw message saying the file already exists
                                            if ($exists) {                                            
                                                $fail("The file '{$fileName}' already exists.");
                                            }
                                        }
                                    };
                                },
                            ]),
                ])->columnSpan('full')
            ])->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $parser = new Parser();

        $filePath = $data['file_path'];   
        
        $fileContents = $parser->parseFile(storage_path('app/public/' . $filePath))->getText();  // Extract the text
 
        $data['file_content'] = $fileContents; // Insert the content in the $data array

        // Save the data to the database
        Document::create([
            'title' => $data['title'],
            'file_name' => $data['file_name'], 
            'file_type' => $data['file_type'],
            'file_date' => $data['file_date'],
            'file_path' => $data['file_path'], 
            'description' => $data['description'], 
            'file_content' => $data['file_content'], 
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Document saved!')
            ->send();

        $this->form->fill();
    }

    // Function for the clear button
    public function clear(): void
    {
        $this->form->fill();

        Notification::make()
        ->success()
        ->title('Form Cleared!')
        ->send();
    }
}
