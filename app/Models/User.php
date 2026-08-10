<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the receipts created by the user.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'user_id', 'user_id');
    }

    /**
     * Get the suggestions created by the user.
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class, 'user_id', 'user_id');
    }

    /**
     * Get the comments created by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id', 'user_id');
    }

    /**
     * Get the user's favorite receipts.
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Receipt::class, 'favorites', 'user_id', 'receipt_id');
    }

    /**
     * Get the receipts liked by the user.
     */
    public function likedReceipts(): BelongsToMany
    {
        return $this->belongsToMany(Receipt::class, 'likes_receipts', 'user_id', 'receipt_id');
    }

    /**
     * Get the comments liked by the user.
     */
    public function likedComments(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class, 'likes_comments', 'user_id', 'comment_id');
    }

    /**
     * Get the suggestions liked by the user.
     */
    public function likedSuggestions(): BelongsToMany
    {
        return $this->belongsToMany(Suggestion::class, 'likes_suggestions', 'user_id', 'suggestion_id');
    }
}
