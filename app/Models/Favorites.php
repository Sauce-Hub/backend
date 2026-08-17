<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorites extends Model
{
    public function user()
    {
        return $this->belongsToMany(User::class, 'favorites', 'receipt_id', 'user_id');
    }
}
