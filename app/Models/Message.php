<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id', 'receiver_id', 'content', 'image',
        'read_at', 'pinned_at',
        'deleted_for_sender_at', 'deleted_for_receiver_at', 'deleted_for_everyone_at',
    ];

    protected $casts = [
        'read_at'                 => 'datetime',
        'pinned_at'               => 'datetime:Y-m-d H:i:s',
        'deleted_for_sender_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_for_receiver_at' => 'datetime:Y-m-d H:i:s',
        'deleted_for_everyone_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ?? null;
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
