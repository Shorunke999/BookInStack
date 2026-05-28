<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'developer_id',
        'name',
        'slug',
        'description',
        'booking_mode',
        'status',
        'reservation_unit',
        'enable_negotiate',
        'whatsapp_number',
        'enable_booking_window',
        'booking_window',
        'widget_config',
        'sort_order',
        'public_key',
        'fraud_config',
        'sms_number',
        'enable_sms_notification',
    ];

    protected $casts = [
        'enable_negotiate'      => 'boolean',
        'enable_booking_window' => 'boolean',
        'booking_window'        => 'array',
        'widget_config'         => 'array',
        'fraud_config'          => 'array',
        'sms_number'            => 'string',
    ];

    // ── Boot: auto-generate slug ───────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name) . '-' . Str::random(5);
            }
            if (empty($service->public_key)) {
            $service->public_key = 'pk_svc_' . \Illuminate\Support\Str::random(32);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }

    public function bookingCategories()
    {
        return $this->hasMany(BookingCategory::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function staff()
    {
        return $this->belongsToMany(
            \App\Models\Developer::class,
            'developer_service',
            'service_id',
            'developer_id'
        )->withTimestamps();
    }
    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForMode($query, string $mode)
    {
        return $query->where('booking_mode', $mode);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Returns the same modeConfig array shape the old Developer::modeConfig()
     * returned, so existing views need minimal changes.
     */
    public function modeConfig(): array
    {
        return match ($this->booking_mode) {

            'ticket' => [
                'mode' => 'ticket',
                'label' => 'Ticket',
                'plural' => 'Tickets',
                'cta' => 'Buy Ticket',
                'amount_label' => 'Ticket Price',
                'desc_label' => 'Event Name',
                'desc_placeholder' => 'e.g. Tech Conference 2025',
                'attendance_label' => 'Checked In',
                'success_message' => 'Ticket confirmed!',
                'supports_quantity' => true,
                'supports_dates' => false,
                'supports_time' => false,
                'supports_daterange' => false,
            ],

            'reservation' => [
                'mode' => 'reservation',
                'label' => 'Reservation',
                'plural' => 'Reservations',
                'cta' => 'Reserve Now',
                'amount_label' => 'Rate per Night',
                'desc_label' => 'Room / Space',
                'desc_placeholder' => 'e.g. Deluxe Room, Event Hall A',
                'attendance_label' => 'Checked Out',
                'success_message' => 'Reservation confirmed!',
                'supports_quantity' => false,
                'supports_dates' => false,
                'supports_time' => false,
                'supports_daterange' => true,   // check_in / check_out
            ],

            default => [  // appointment
                'mode' => 'appointment',
                'label' => 'Appointment',
                'plural' => 'Appointments',
                'cta' => 'Book Appointment',
                'amount_label' => 'Service Fee',
                'desc_label' => 'Service',
                'desc_placeholder' => 'e.g. Hair cut, Legal consultation',
                'attendance_label' => 'Attended',
                'success_message' => 'Appointment booked!',
                'supports_quantity' => false,
                'supports_dates' => true,   // preferred_date + preferred_time
                'supports_time' => true,
                'supports_daterange' => false,
            ],
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function modeLabel(): string
    {
        return ucfirst($this->booking_mode);
    }

    public function modeBadgeColor(): string
    {
        return match($this->booking_mode) {
            'ticket'      => '#6366f1',
            'reservation' => '#10b981',
            'appointment' => '#f59e0b',
            default       => '#64748b',
        };
    }
    public function regeneratePublicKey(): string
    {
        $key = 'pk_svc_' . \Illuminate\Support\Str::random(32);
        $this->update(['public_key' => $key]);
        return $key;
    }
    public function fraudConfig(): array
    {
        return $this->fraud_config ?? [
            'velocity' => [
                '3' => ['count' => 5, 'score' => 10],
                '5' => ['count' => 10, 'score' => 20],
                '10' => ['count' => 20, 'score' => 35],
            ],

            'amount' => [
                'score' => 15,
            ],
        ];
    }
}
