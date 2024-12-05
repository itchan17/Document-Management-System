<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Folder extends Model
{
    use HasFactory; 
    use SoftDeletes;

    protected $fillable = [
        'folder_name',
        'created_by',
        'date_modified',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::deleting(function ($folder) {
    //         // Ensure soft delete cascades to children
    //         if (auth()->check()) {

    //             $folder->deleted_by = auth()->id(); // Get user that deleted the folder

    //             $folder->save();

    //             // apply softdelete to each child
    //             $folder->documents()->each(function ($document) {

    //                 if (is_null($document->deleted_through_folder)) {

    //                     // document inside will be hidden in the deleted files to recover the files you need to restore the folder
    //                     $document->deleted_through_folder = 1;

    //                     $document->delete();
    //                 }
                   
                    
    //             });
    //         }
            
    //     });

    //    static::restored(function ($folder) {
    //         $folder->documents()->each(function ($document) {
    //             // Restore the soft-deleted document if it was deleted
    //             if ($document->trashed()) {
                    
    //                 $document->deleted_through_folder = null;

    //                 $document->restore();
    //             }
    //         });
    //     }); 
    // }
}
