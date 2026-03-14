<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'developer_id',
        'paystack_reference',
        'amount',
        'platform_fee',
        'developer_amount',
        'paystack_fee',
        'currency',
        'channel',
        'ip_address',
        'paystack_metadata',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paystack_metadata' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'float',
        'platform_fee' => 'float',
        'developer_amount' => 'float',
        'paystack_fee' => 'float',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }
}
