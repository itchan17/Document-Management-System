<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = [
        'id',   
        'causer_id',    
        'subject_id',   
        'description',
        'created_at',
    ];

    // To access user's name in the Users table using the causer_id foreign key
    public function user()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
    
    // To access document's title in the Document table using subject_id foreign key
    public function document()
    {
        return $this->belongsTo(Document::class, 'subject_id')->withTrashed();
    }
}
