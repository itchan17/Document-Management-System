<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;
    use LogsActivity;

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
    


}
