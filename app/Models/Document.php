<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

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

    protected $fillable = [ 'title', 
                            'file_name',
                            'file_path', 
                            'file_date', 
                            'file_type', 
                            'user_id', 
                            'file_content', 
                            'description'
                        ];


    
    protected static $logAttributes = ['title', 'file_name', 'file_path', 'file_date', 'file_type',  'description' ];

    protected static $logName = 'document';

    protected static $logOnlyDirty = true;

    // Customize the activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document') // Custom Name
            ->setDescriptionForEvent(fn(string $eventName) => "Document has been {$eventName}.") // Custom description
            ->logOnly(['title', 'file_name', 'file_path', 'file_date', 'file_type',  'description' ]) // Showing the Activities
            ->logOnlyDirty(); // Show only the changed attributes (EDIT)
    }

    // To access user's name in the Users table using the user_id foreign key
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    


}
