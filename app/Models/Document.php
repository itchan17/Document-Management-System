<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Document extends Model
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
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
}
