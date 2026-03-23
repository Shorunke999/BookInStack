<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class Developer extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    use HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'owner_id',

        'bvn_verified',

        'enable_booking_window',
        'booking_window',

        'public_key',
        'secret_key',

        'paystack_subaccount_code',
        'paystack_subaccount_id',

        'business_name',
        'bank_code',
        'account_number',
        'status',

        'booking_mode',
        'max_per_order',

        'email_verified_at',
         'widget_config',
         'reservation_unit',
         'enable_negotiate',
            'whatsapp_number',
            'platform_fee_percent',
            'booking_expires_at'

    ];

    protected $hidden = [
        'password',
        'secret_key',
        'remember_token'
    ];

    protected $casts = [
        'bvn_verified' => 'boolean',
        'password' => 'hashed',
        'enable_booking_window' => 'boolean',
        'booking_window' => 'array',
        'email_verified_at' => 'datetime',
         'widget_config'=> 'array',
         'platform_fee_percent' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

     public function bookingCategories()
    {
        return $this->hasMany(BookingCategory::class);
    }
    // Get the account owner — for staff this returns their admin, for admin returns self
    public function owner()
    {
        return $this->belongsTo(Developer::class, 'owner_id');
    }

    public function staff()
    {
        return $this->hasMany(Developer::class, 'owner_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->bvn_verified;
    }

    public function totalRevenue(): float
    {
        return $this->payments()
            ->where('status', 'success')
            ->sum('developer_amount') ; // convert kobo → naira
    }

    public function totalBookings(): int
    {
        return $this->bookings()->where('status', 'paid')->count();
    }

    public function isWithinBookingWindow(): bool
    {
        if (! $this->enable_booking_window) {
            return true; // always open
        }

        $window = $this->booking_window;
        if (empty($window)) {
            return true;
        }

        $tz = $window['timezone'] ?? 'Africa/Lagos';
        $now = \Carbon\Carbon::now($tz);

        // Check day of week (Carbon: 0=Sunday, 1=Monday ... 6=Saturday)
        $allowedDays = $window['days'] ?? [1, 2, 3, 4, 5];
        if (! in_array($now->dayOfWeek, $allowedDays)) {
            return false;
        }

        // Check time range
        $start = \Carbon\Carbon::createFromFormat('H:i', $window['start'] ?? '00:00', $tz);
        $end = \Carbon\Carbon::createFromFormat('H:i', $window['end'] ?? '23:59', $tz);

        return $now->between($start, $end);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }
     public function effectiveDeveloper(): Developer
    {
        return $this->isStaff() ? $this->owner : $this;
    }
    public function isSuperAdmin(): bool
{
    return $this->email === config('app.superadmin_email');
}
       public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    // Staff use their admin's subaccount and keys
    public function effectiveSubaccountCode(): string
    {
        return $this->isStaff()
            ? $this->owner->paystack_subaccount_code
            : $this->paystack_subaccount_code;
    }

    // Helper method — add to model:
    public function platformFeeKobo(int $amountKobo): int
    {
        $percent = $this->platform_fee_percent ?? 5.00;
        return (int) round($amountKobo * ($percent / 100));
    }

    public function developerShareKobo(int $amountKobo): int
    {
        return $amountKobo - $this->platformFeeKobo($amountKobo);
    }
    /**
     * Full config for the current booking mode.
     * Used by the API status endpoint and dashboard views.
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
}
