<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'customer_id',
        'invoice_number',
        'status',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid',
        'balance',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    // Relationships

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

?>