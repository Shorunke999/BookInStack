<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCategory extends Model
{

    protected $fillable = [
        'developer_id',
        'booking_mode',
        'name',
        'description',
        'price',
        'child_price',
        'duration_minutes',
        'capacity',
        'enable_child_pricing',
        'max_per_order',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'enable_child_pricing' => 'boolean',
        'price'                => 'integer',
        'child_price'          => 'integer',
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
        return '₦' . number_format($this->price / 100, 2);
    }

    public function formattedChildPrice(): ?string
    {
        return $this->child_price
            ? '₦' . number_format($this->child_price / 100, 2)
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
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
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
        $paid = $this->bookings()->where('status', 'paid');
 
        if ($this->booking_mode === 'ticket') {
            return (int) $paid->selectRaw('COALESCE(SUM(adults + children), 0) as total')
                              ->value('total');
        }
 
        return $paid->count();
    }
 
    public function slotsRemaining(): ?int
    {
        if ($this->total_slots === null) return null;
        return max(0, $this->total_slots - $this->slotsBooked());
    }
 
    public function isFull(): bool
    {
        if ($this->total_slots === null) return false;
        return $this->slotsBooked() >= $this->total_slots;
    }
}
