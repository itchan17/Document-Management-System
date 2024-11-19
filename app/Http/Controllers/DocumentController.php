<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function view($id)
    {
        $document = Document::findOrFail($id);
        
        // Check if the file exists in the specified disk
        if (Storage::disk('local')->exists($document->file_path)) {
            // Return the file response for viewing
            return response()->file(Storage::disk('local')->path($document->file_path));
        } else {
            return redirect()->back()->with('error', 'File not found.');
        }

        return redirect(route('filament.admin.auth.login'));
    }
}
