<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $table = 'Recommendations';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'receipt_id',
        'seen',
    ];

    protected $casts = [
        'seen' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id', 'receipt_id');
    }
}
