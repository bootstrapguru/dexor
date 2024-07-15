<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'role',
        'content',
        'name',
        'tool_call_id',
        'tool_calls',
    ];

    protected $casts = [
        'tool_calls' => 'array',
    ];

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
