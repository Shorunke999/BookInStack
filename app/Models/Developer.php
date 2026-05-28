<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
    use App\Enums\OnboardingStatus;
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
            'sms_number',
            'platform_fee_percent',
            'booking_expires_at',
            'allowed_domains',
            'active_service_id',
            'onboarding_status'

    ];

    protected $hidden = [
        'password',
        'secret_key',
        'remember_token'
    ];

    protected $casts = [
        'password' => 'hashed',
        'enable_booking_window' => 'boolean',
        'booking_window' => 'array',
        'email_verified_at' => 'datetime',
         'widget_config'=> 'array',
         'platform_fee_percent' => 'decimal:2',
         'allowed_domains' => 'array',
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

    public function services()
{
    return $this->hasMany(\App\Models\Service::class)->orderBy('sort_order');
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
    public function assignedServices()
    {
        return $this->belongsToMany(
            \App\Models\Service::class,
            'developer_service',
            'developer_id',
            'service_id'
        )->withTimestamps();
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

    // Relationships
    public function onboarding(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Onboarding::class);
    }

    // Replace bvn_verified checks everywhere with this:
    public function isVerified(): bool
    {
        return $this->onboarding_status === OnboardingStatus::Complete;
    }

    public function isOnboarded(): bool
    {
        return in_array($this->onboarding_status, [
            OnboardingStatus::AccountCreated,
            OnboardingStatus::Complete,
        ]);
    }



/**
 * Switch the active service context for this developer.
 * Call from ServiceController@activate.
 */
public function switchService(\App\Models\Service $service): void
{
    //abort_if($service->developer_id !== $this->id, 403);
    $this->update(['active_service_id' => $service->id]);
}
/**
 * Returns the services this developer can see.
 * Admins → all their services.
 * Staff  → only their assigned services (scoped to their owner).
 */
public function accessibleServices()
{
    if ($this->isStaff()) {
        return $this->assignedServices()->where('developer_id_owner', $this->owner_id);
        // Simpler: just return assigned directly — pivot already scopes correctly
    }

    // Admin — return all their services
    return $this->services();
}

/**
 * Resolve which services are visible to this user.
 * Returns a Collection (not a query builder) for convenience in controllers/views.
 */
public function visibleServices(): \Illuminate\Database\Eloquent\Collection
{
    if ($this->isStaff()) {
        // Staff sees only assigned services from their owner
        return $this->assignedServices()
                    ->whereHas('developer', fn($q) => $q->where('id', $this->owner_id))
                    ->active()
                    ->orderBy('sort_order')
                    ->get();
    }

    return $this->services()->active()->orderBy('sort_order')->get();
}

/**
 * Active service for staff is resolved from their assigned services
 * (falls back to first assigned if stored ID not accessible).
 */
public function activeService(): ?\App\Models\Service
{
    if ($this->isStaff()) {
        $assigned = $this->assignedServices()->active()->orderBy('sort_order')->pluck('services.id');

        if ($this->active_service_id && $assigned->contains($this->active_service_id)) {
            return \App\Models\Service::find($this->active_service_id);
        }

        return \App\Models\Service::find($assigned->first());
    }

    // Admin path — same as before
    if ($this->active_service_id) {
        return $this->services()->find($this->active_service_id);
    }

    return $this->services()->where('status', 'active')->first();
}
/**
 * Keep backward-compat: modeConfig() now delegates to active service.
 * Old code calling $developer->modeConfig() keeps working.
 */
public function modeConfig(): array
{
    return $this->activeService()?->modeConfig() ?? [];
}

/**
 * Convenience: active booking_mode (read from active service).
 * Replaces direct $developer->booking_mode reads in controllers.
 */
public function activeMode(): string
{
    return $this->activeService()?->booking_mode ?? $this->booking_mode;
}
}
