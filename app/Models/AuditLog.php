<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'timestamp',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'timestamp' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Helper to write audit logs easily
     */
    public static function log(string $action, ?string $model = null, ?int $modelId = null, $oldValues = null, $newValues = null): self
    {
        return self::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'timestamp' => now(),
        ]);
    }

    /**
     * Helper to write audit logs easily for Administrators
     */
    public static function logAdmin(string $action, ?string $model = null, ?int $modelId = null, $oldValues = null, $newValues = null): self
    {
        return self::create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'timestamp' => now(),
        ]);
    }
}
