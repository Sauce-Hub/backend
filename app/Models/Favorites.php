<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Favorites extends Model
{
    protected $fillable = [
        'user_id',
        'receipt_id'
    ];

    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'receipt_id', 'user_id');
    }
}
