<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [ 'title', 
                            'file_name',
                            'file_path', 
                            'file_date', 
                            'file_type', 
                            'user_id', 
                            'file_content', 
                            'description'
                        ];
}
