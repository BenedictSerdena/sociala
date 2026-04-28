<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $fillable = ['admin_id', 'action', 'target_type', 'target_id', 'meta'];
    protected $casts = ['meta' => 'array'];

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }

    public static function record(string $action, string $targetType = null, int $targetId = null, array $meta = []): void
    {
        static::create([
            'admin_id'    => auth()->id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'meta'        => $meta ?: null,
        ]);
    }
}
