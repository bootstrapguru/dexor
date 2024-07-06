<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use HasFactory;

    protected $fillable = [
        'assistant_id',
        'title',
        'folder_path'
    ];

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
