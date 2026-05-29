<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCategory extends Model
{

    protected $fillable = [
        'developer_id', 'booking_mode', 'name', 'description',
        'price', 'fixed_price', 'min_price', 'max_price',
        'child_price', 'duration_minutes', 'capacity',
        'enable_child_pricing', 'max_per_order', 'total_slots',
        'sort_order', 'status','service_id',

          // Check-in window
        'checkin_start_date', 'checkin_end_date',
        'checkin_start_time', 'checkin_end_time',
        'checkin_days_before', 'checkin_days_after',
    ];

    protected $casts = [
        'enable_child_pricing' => 'boolean',
        'fixed_price'          => 'boolean',
        'price'                => 'integer',
        'child_price'          => 'integer',
        'min_price'            => 'integer',
        'max_price'            => 'integer',

         'checkin_start_date'   => 'date',
        'checkin_end_date'     => 'date',
        'checkin_days_before'  => 'integer',
        'checkin_days_after'   => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function developer()
    {
        return $this->belongsTo(Developer::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'category_id');
    }

    public function service()
    {
        return $this->belongsTo(\App\Models\Service::class);
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

    public function formattedPrice(): string
    {
        return '₦' . number_format($this->price , 2);
    }

    public function formattedChildPrice(): ?string
    {
        return $this->child_price
            ? '₦' . number_format($this->child_price , 2)
            : null;
    }

    public function priceLabel(string $mode): string
    {
        return match ($mode) {
            'reservation' => $this->formattedPrice() . '/night',
            'ticket'      => $this->formattedPrice() . '/adult',
            default       => $this->formattedPrice(),
        };
    }

    /**
     * API-safe array for the SDK catalog endpoint.
     */
    public function toApiArray(string $mode): array
    {

        $base = [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'price'           => $this->price,
            'fixed_price'     => $this->fixed_price ?? true,
            'min_price'       => $this->min_price,
            'max_price'       => $this->max_price,
            'slots_remaining' => $this->slotsRemaining(),
        ];
        if ($mode === 'ticket') {
            $base['enable_child_pricing'] = $this->enable_child_pricing;
            $base['child_price']          = $this->child_price;
            $base['max_per_order']        = $this->max_per_order;
        }

        if ($mode === 'reservation') {
            $base['capacity'] = $this->capacity;
        }

        if ($mode === 'appointment') {
            $base['duration_minutes'] = $this->duration_minutes;
        }

        return $base;
    }

       // ── Slot tracking ──────────────────────────────────────────────────────────

    public function slotsBooked(): int
    {
        $paid = $this->bookings()->where('payment_status', 'paid')->where('attended', false);

        if ($this->booking_mode === 'ticket') {
            return (int) $paid->selectRaw('COALESCE(SUM(adults + children), 0) as total')
                              ->value('total');
        }

        return $paid->count();
    }
   public function activeBookings(): int
    {
        $query = $this->bookings()
            ->where('payment_status', 'paid')
            ->where('attended', false);

        if ($this->booking_mode === 'ticket') {
            return (int) $query
                ->selectRaw('COALESCE(SUM(adults + children), 0) as total')
                ->value('total');
        }

        return $query->count();
    }

    public function slotsRemaining(): ?int
    {
        if ($this->total_slots === null) return null;
        return max(0, $this->total_slots - $this->activeBookings());
    }

    public function isFull(): bool
    {
        if ($this->total_slots === null) return false;
        return $this->slotsBooked() >= $this->total_slots;
    }
    /**
     * Scope to a specific service.
     */
    public function scopeForService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }
}
