<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\Folder;
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
use thiagoalessio\TesseractOCR\TesseractOCR;
use Intervention\Image\Facades\Image;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Database\QueryException;
use Filament\Actions\Contracts\HasActions;

class CreateDocument extends Page implements HasForms, HasActions
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-s-arrow-up-on-square';

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
                Section::make('Upload File')
                ->columns([
                    'sm' => 1,
                    'md' => 3,                 
                ])
                ->schema([
                    // Uploading images
                    FileUpload::make('file_path')
                        ->required()
                        ->maxSize(12 * 1024)
                        ->label('Image') 
                        ->disk('local')
                        ->directory('documents')
                        ->storeFileNamesIn('file_name')   
                        ->multiple()
                        ->columnSpan([
                            'sm' => 1,
                            'md' => 2,                 
                        ])
                        ->visible(function (Get $get) {
                            return $get('file_type') == 'image';
                        })
                        ->acceptedFileTypes([
                            'image/jpeg',     
                            'image/png',   
                            'image/webp'        
                        ])
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
                                            $fail("The image '{$fileName}' already exists.");
                                        }
                                    }
                                };
                            },
                            function () {
                                return function (string $attribute, $value, Closure $fail) {

                                    $fileName = $value->getClientOriginalName(); 

                                    try {

                                        // try to extract text from the file
                                        $text = (new TesseractOCR($value->getRealPath()))->lang('eng')->run(); 
                
                                    }  
                                    catch (Exception $e) {

                                        // send an error thath there's no text that can be extracted
                                        $fail("No readable text found in '{$fileName}'.");

                                    }                                                            
                                    
                                };
                            },
                        ]),

                    // Uploading file
                    FileUpload::make('file_path')
                        ->visible(function (Get $get) {
                            return $get('file_type') == 'pdf';
                        })
                        ->columnSpan([
                            'sm' => 1,
                            'md' => 2,                 
                        ])
                        ->maxSize(12 * 1024)
                        ->required()
                        ->label('File')                 
                        ->disk('local')
                        ->directory('documents')
                        ->storeFileNamesIn('file_name')   
                        ->acceptedFileTypes([
                            'application/pdf',       
                        ])            
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
                            function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    $fileName = $value->getClientOriginalName(); 
                            
                                    if ($value) {  
                                        try {
                                            $filePath = $value->getRealPath();
                                                                                                            
                                            // Second check: Try a basic PDF header check
                                            $handle = fopen($filePath, 'rb');
                                            if ($handle) {
                                                $header = fread($handle, 4);
                                                fclose($handle);
                                                if ($header !== '%PDF') {
                                                    $fail("The file '{$fileName}' appears to be corrupted.");
                                                    return;
                                                }
                                            }

                                            // Try parsing with PDFParser
                                            try {
                                                $parser = new Parser();
                                                
                                                // Use a different approach to extract text
                                                $pdf = @$parser->parseFile($filePath);
                                                
                                                // If parsing succeeded, try to extract pages and text
                                                if ($pdf) {
                                                    $pages = $pdf->getPages();
                                                    $text = '';
                                                    
                                                    // Extract text page by page
                                                    foreach ($pages as $page) {
                                                        try {
                                                            $text .= $page->getText() . "\n";
                                                        } catch (\Throwable $e) {
                                                            continue; // Skip problematic pages
                                                        }
                                                    }
                                                    
                                                    if (empty(trim($text))) {
                                                        $fail("No readable text found in '{$fileName}'.");
                                                        return;
                                                    }
                                               
                                                } else {
                                                    $fail("Unable to parse '{$fileName}'. The file might be corrupted or encrypted.");
                                                    return;
                                                }
                                                
                                            } catch (\Exception $e) {
                                                \Log::error("PDF Parse Error for {$fileName}: " . $e->getMessage());
                                                $fail("Unable to process '{$fileName}'. Please ensure it's a valid PDF document.");
                                                return;
                                            }
                                            
                                        } catch (\Exception $e) {
                                            \Log::error("File Processing Error: " . $e->getMessage());
                                            $fail("An error occurred while processing the file. Please try again.");
                                            return;
                                        }
                                    }
                                };
                            },
                        ]), 
                        Select::make('file_type')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $set('file_path', null);
                            $set('file_name', null);
                        })
                        ->options([
                            'image' => 'Image',
                            'pdf' => 'PDF',
                        ])
                        ->default('pdf')             
                ])->columnSpan('full'),
                Hidden::make('file_extension'),
                Section::make('Document Details')
                ->columns([
                    'sm' => 1,
                    'md' => 3,                 
                ])
                ->schema([
                    TextInput::make('title')
                            ->required()
                            ->label('Title')
                            ->maxLength(255)
                            ->rules(['regex:/^[a-zA-Z0-9\s_-]*$/'])
                            ->unique(table: Document::class)
                            ->validationMessages([
                                'unique' => 'The :attribute already exists.',
                            ]),
                    DatePicker::make('file_date')
                        ->required()
                        ->label('File Date'),
                    Select::make('folder_id')
                        ->label('Select Folder')  
                        ->options(Folder::all()->pluck('folder_name', 'id'))
                        ->suffixIcon('heroicon-s-folder'),
                    TextArea::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->columnSpan('full')
                        ->rows(3),
                               
                ])->columnSpan('full'),
            ])->statePath('data');
    }

    public function createAction(): Action
    {
        return Action::make('create')
            
            ->label('Upload')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-information-circle')
            ->modalHeading('Upload Document')
            ->modalDescription('Are you sure you want to upload this document?')
            ->modalSubmitActionLabel('Confirm')
            ->modalCancelActionLabel('Cancel')
            ->action(function () {
                $this->closeActionModal();
                $this->create();
            });     
    }

    public function clearAction(): Action
    {
        return Action::make('clear')
            
            ->label('Clear') 
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Clear Form')
            ->modalDescription('Are you sure you want to clear this form?')
            ->modalSubmitActionLabel('Confirm')
            ->modalCancelActionLabel('Cancel')
            ->action(function () {
                $this->closeActionModal();
                $this->clear();
            });     
    }
   
    public function create()
    {
            $data = $this->form->getState();
           
            // extract the file
            $data['file_content'] = ($this->extractContent($data));
            
            // check if file_path is an array which means it is multiple images
            // then run the method that compile those images in pdf
            if (is_array($data['file_path']) && count($data['file_path']) == 1 && $data['file_type'] == 'image'){
                
                $data['file_path'] = $data['file_path'][0];

                $data['file_name'] = $data['file_name'][$data['file_path']];

            }
            elseif (is_array($data['file_path']) && count($data['file_path']) > 1  && $data['file_type'] == 'image') {

                $file_path_arr = $data['file_path'];

                $newData = ($this->convertImagesToPDF($data['file_path'], $data['title']));

                $data['file_path'] =  $newData['file_path'];
                $data['file_name'] =  $newData['file_name'];
                $data['file_type'] =  $newData['file_type'];

                foreach($file_path_arr as $path){
                    Storage::disk('local')->delete($path);
                }
            } 
            
            // Add date and time if new document is added in the folder
            $this->updateDateModified($data['folder_id']);

          

            try{

                // Save the data to the database
                Document::create([
                    'title' => $data['title'],
                    'file_name' => $data['file_name'], 
                    'folder' => $data['folder_id'],
                    'file_date' => $data['file_date'],
                    'file_path' => $data['file_path'], 
                    'description' => $data['description'], 
                    'file_content' => $data['file_content'], 
                    'file_type' => $data['file_type'], 
                    'user_id' => auth()->id(),
                ]);

                Notification::make()
                ->success()
                ->title('Document saved')
                ->send();

                $this->form->fill();
            }
            catch(QueryException $e){ 

                // Will check if the file content is too long

                Notification::make()
                ->danger()
                ->title('File content is too long')
                ->send();

                // Delete thefile in file system
                if (Storage::disk('local')->exists($data['file_path'])) {

                    // delete the file in the database
                    Storage::disk('local')->delete($data['file_path']);

                }
                $this->data['file_path'] = null;
              
            }       
    }

    // Update the date_modified column of folders
    public function updateDateModified($id):void 
    {
        if($id){

            // find the row
            $folder = Folder::find($id);
            
            // get the current time and change the format
            $date_modified = Carbon::now(config('app.timezone'))->format('Y-m-d H:i:s');

            // update the column
            $folder->date_modified = $date_modified;

            $folder->save();

        }
    }

    public function extractContent($data):string 
    {
        $parser = new Parser();

        // extract content for single image upload
        if (is_array($data['file_path']) && count($data['file_path']) == 1 && $data['file_type'] == 'image'){

            $filePath = $data['file_path'][0];

            // Process the image and extract text
            $file_content = (new TesseractOCR(storage_path('app/private/' . $filePath)))->lang('eng')->run();  

            return $file_content;
        }  
        
        // extract content for multiple image upload
        elseif (is_array($data['file_path']) && count($data['file_path']) > 1 && $data['file_type'] == 'image'){
            $filePaths = $data['file_path'];
            $file_content = "";

            foreach($filePaths as $filePath){

                // Process the image and extract text
                $text = (new TesseractOCR(storage_path('app/private/' . $filePath)))->lang('eng')->run();  

                // concatinate the extracted text
                $file_content .= $text . "\n";    
            }
           
            return $file_content;
        }   

        // extract content for pdf
        elseif (!is_array($data['file_path']) && $data['file_type'] == 'pdf') {

            $filePath = storage_path('app/private/' . $data['file_path']);
            $file_content = ''; // Default value to ensure a string is returned
        
            try {
                $parser = new Parser();
        
                // Parse the PDF file
                $pdf = $parser->parseFile($filePath);
        
                // If parsing succeeded, try to extract pages and text
                if ($pdf) {
                    $pages = $pdf->getPages();
        
                    // Extract text page by page
                    foreach ($pages as $page) {
                        try {
                            $file_content .= $page->getText() . "\n";
                        } catch (\Throwable $e) {
                            // Log error for problematic pages and continue
                            \Log::error("Error extracting text from a page: " . $e->getMessage());
                            continue;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error("PDF Parse Error for {$filePath}: " . $e->getMessage());
                dd($e); // Optional debug output
            }
   
            return $file_content;
        }
        
        
    }

    public function convertImagesToPDF(array $image_paths, string $title): array
    {
       
        $html = "";  // store html code

        $enryptedFileName =  encrypt($title);

        // loops over the array of file_path
        foreach($image_paths as $path) {

            $html .= '<img src="' . storage_path('app/private/' . $path) . '" style="width: 100%; height: auto;">';
        }

        $pdf = Pdf::loadHtml($html); // convert html into pdf

        $pdf->save(storage_path('app/private/documents/' . $enryptedFileName . '.pdf'));  // Save pdf in the filesystem

        return 
        [
            'file_path' => 'documents/' . $enryptedFileName . '.pdf', 
            'file_name' =>  $title . '.pdf',
            'file_type' =>  'pdf'
        ];
    }

    // Function for the clear button
    public function clear(): void
    {
        $this->form->fill();

        Notification::make()
        ->success()
        ->title('Form cleared!')
        ->send();
    }
}
