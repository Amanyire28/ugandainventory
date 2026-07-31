<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PwaDevice extends Model
{
    protected $table = 'pwa_devices';

    protected $fillable = [
        'device_uuid',
        'device_name',
        'business_id',
        'user_id',
        'app_version',
        'last_online_at',
        'last_sync_at',
        'status',
    ];

    protected $casts = [
        'last_online_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
