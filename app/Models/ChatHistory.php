<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatHistory extends Model
{
    protected $table = 'chat_history';

    public $timestamps = false;

    protected $fillable = [
        'user_prompt',
        'response',
        'timestamp',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
