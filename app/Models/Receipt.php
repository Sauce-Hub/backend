<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'receipts';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'receipt_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'caption',
        'category',
        'estimated_time',
        'Calories',
        'Fats',
        'Carbs',
        'Protein',
        'timestamp',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Calories' => 'integer',
            'Fats' => 'integer',
            'Carbs' => 'integer',
            'Protein' => 'integer',
            'timestamp' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the receipt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the ingredients for the receipt.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'receipt_id', 'receipt_id');
    }

    /**
     * Get the users who favorited the receipt.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'receipt_id', 'user_id');
    }

    /**
     * Get the users who liked the receipt.
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes_receipts', 'receipt_id', 'user_id');
    }

    /**
     * Get the comments for the receipt.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'receipt_id', 'receipt_id');
    }

    /**
     * Get the suggestions for the receipt.
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class, 'receipt_id', 'receipt_id');
    }
}
