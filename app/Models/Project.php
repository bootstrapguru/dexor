<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'assistant_id',
    ];

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }
}
