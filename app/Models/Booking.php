<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'booking_mode',
        'developer_id',
        'category_id',
        'amount',
        'description',
        'customer_email',
        'customer_name',
        'customer_phone',
        'metadata',
        'status',
        'paystack_reference',
        'paystack_access_code',
        'payment_url',
        'attended',
        'attended_at',
        'attendance_note',
        'paid_at',
        'quantity',
        'check_in',
        'check_out',
        'preferred_date',
        'preferred_time',
        'adults',
        'children',
        'booked_by_id',
        'booked_by_type',
        'booked_via',
        'booking_expires_at',
        'attended_by_id',
        'payment_link_token'

    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'float',
        'attended' => 'boolean',
        'attended_at' => 'datetime',
        'check_in' => 'date',
        'check_out' => 'date',
        'preferred_date' => 'date',

    ];

    // Auto-generate reference on create
    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->reference)) {
                $booking->reference = 'BKG-'.strtoupper(Str::random(12));
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }

    public function category()
    {
        return $this->belongsTo(BookingCategory::class, 'category_id');
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Relations
public function bookedBy()   { return $this->belongsTo(\App\Models\Developer::class, 'booked_by_id'); }
public function attendedBy() { return $this->belongsTo(\App\Models\Developer::class, 'attended_by_id'); }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function amountInNaira(): float
    {
        return $this->amount;
    }

    public function markAsPaid(string $paystackReference): void
    {
        $this->update([
            'status' => 'paid',
            'paystack_reference' => $paystackReference,
            'paid_at' => now(),
        ]);
    }


    /** Total amount for ticket mode (unit price × quantity) */
    public function totalAmount(): int
    {
        return (int) ($this->amount * $this->quantity);
    }

    /** Number of nights for reservation mode */
    public function nights(): ?int
    {
        if (! $this->check_in || ! $this->check_out) {
            return null;
        }

        return $this->check_in->diffInDays($this->check_out);
    }
}
