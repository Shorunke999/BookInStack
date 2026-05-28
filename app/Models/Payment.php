<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->paystack_reference)) {
                $payment->paystack_reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        return 'PSK-' . strtoupper(Str::random(16));
    }
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
