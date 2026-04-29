<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    use ResolvesMediaUrl;

    protected $fillable = ['user_id', 'image', 'expires_at', 'archived_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())->whereNull('archived_at');
    }

    public function likes()
    {
        return $this->hasMany(StoryLike::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->resolveMediaUrl($this->image) ?? '';
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getIsLikedAttribute(): bool
    {
        if (!auth()->check()) return false;
        return $this->likes()->where('user_id', auth()->id())->exists();
    }
}
