<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content', 'image', 'visibility'];

    protected $appends = ['image_url', 'likes_count', 'comments_count', 'is_liked', 'is_bookmarked'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->with('user')->latest();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getIsLikedAttribute(): bool
    {
        if (!auth()->check()) return false;
        return $this->likes()->where('user_id', auth()->id())->exists();
    }

    public function getIsBookmarkedAttribute(): bool
    {
        if (!auth()->check()) return false;
        return $this->bookmarks()->where('user_id', auth()->id())->exists();
    }
}
