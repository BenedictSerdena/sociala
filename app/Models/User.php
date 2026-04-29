<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use ResolvesMediaUrl;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'avatar',
        'cover_photo',
        'is_admin',
        'banned_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['avatar_url', 'cover_photo_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at'         => 'datetime',
            'is_admin'          => 'boolean',
            'password'          => 'hashed',
        ];
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function userNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->resolveMediaUrl($this->avatar)
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff';
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->resolveMediaUrl($this->cover_photo);
    }
}
