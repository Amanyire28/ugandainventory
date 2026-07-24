<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'features',
        'price',
        'billing_cycle_days',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'billing_cycle_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get businesses subscribed to this package
     */
    public function businesses()
    {
        return $this->hasMany(Business::class, 'subscription_plan', 'slug');
    }
}
