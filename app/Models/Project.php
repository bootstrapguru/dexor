<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
    ];

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(Assistant::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }
}
