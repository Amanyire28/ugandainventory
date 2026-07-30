<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
        'selling_price',
        'cost_price_at_sale',
        'discount',
        'vat',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price_at_sale' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function getSellingPriceAttribute()
    {
        return $this->attributes['selling_price'] ?? $this->unit_price;
    }

    public function setSellingPriceAttribute($value)
    {
        $this->attributes['selling_price'] = $value;
        $this->attributes['unit_price'] = $value;
    }

    public function getSubtotalAttribute()
    {
        return $this->attributes['subtotal'] ?? $this->total;
    }

    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = $value;
        $this->attributes['total'] = $value;
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = $value;
        $this->attributes['selling_price'] = $value;
    }

    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = $value;
        $this->attributes['subtotal'] = $value;
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}