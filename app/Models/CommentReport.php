<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $fillable = ['user_id', 'comment_id', 'reason', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function comment() { return $this->belongsTo(Comment::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
