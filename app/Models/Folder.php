<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_name',
        'created_by',
        'date_modified',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder');
    }
}
