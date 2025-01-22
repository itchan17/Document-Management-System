<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Activity;

class Document extends Model
{
    use Searchable;
    use SoftDeletes;
    use LogsActivity;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'file_date' => $this->file_date,
            'file_content' => $this->file_content,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,     
        ];
    }

    // protected $casts = [
    //     'file_path' => 'array',
    // ];

    protected $fillable = [ 'title', 
                            'file_name',
                            'file_path', 
                            'file_date', 
                            'folder', 
                            'user_id', 
                            'file_content', 
                            'description',
                            'file_type'
                        ];
                        
    public function getFolder()
    {
        return $this->belongsTo(Folder::class, 'folder');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    
    
    
 

    protected static function booted()
    {
        static::deleting(function ($document) {
            if (auth()->check()) {
                $document->deleted_by = auth()->id(); // Get user that deleted the document
                $document->save();
            }
        });
    }
    
    protected static $logAttributes = ['title', 'file_name', 'file_path', 'file_date', 'file_type',  'description' ];

    protected static $logName = 'document';
    
    protected static $logOnlyDirty = true;

    // Customize the activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document') // Custom Name
            ->setDescriptionForEvent(fn(string $eventName) => "Document has been {$eventName}.") // Custom description
            ->logOnly(['title', 'file_name', 'file_date', 'file_type', 'description' ]) // Showing the Activities
            ->logOnlyDirty() // Show only the changed attributes 
            ->dontSubmitEmptyLogs();
    }

    // Log the original title and file_name
    public function tapActivity(Activity $activity)
    {
        // check if the original array is null, if null which means the operation is create
        if(!$this->getOriginal()){
            $activity->subject_title = $this->getAttributes()['title'];
            $activity->subject_file_name = $this->getAttributes()['file_name'];
        }
        // if not null the operation is update
        elseif($this->getOriginal()){
            $activity->subject_title = $this->getOriginal()['title'];
            $activity->subject_file_name = $this->getOriginal()['file_name'];
        }
    }
}
