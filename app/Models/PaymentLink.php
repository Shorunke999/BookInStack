<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    protected $fillable = [
        'token', 'developer_id', 'category_id',
        'customer_name', 'customer_email', 'customer_phone',
        'amount', 'description', 'note',
        'status', 'expires_at', 'paid_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at'    => 'datetime',
        'amount'     => 'integer',
    ];

    public function developer() { return $this->belongsTo(Developer::class); }
    public function category()  { return $this->belongsTo(BookingCategory::class, 'category_id'); }

    public static function generateToken(): string
    {
        do { $token = Str::random(12); }
        while (static::where('token', $token)->exists());
        return $token;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function formattedAmount(): string
    {
        return '₦' . number_format($this->amount , 2);
    }

    public function publicUrl(): string
    {
        return url('/pay/' . $this->token);
    }

    public function whatsappText(): string
    {
        $lines = [
            "Hello {$this->customer_name},",
            "",
            "Please use the link below to complete your payment.",
            "",
            "📋 *Details*",
            "Service: {$this->description}",
            "Amount: {$this->formattedAmount()}",
        ];
        if ($this->expires_at) {
            $lines[] = "Expires: {$this->expires_at->format('d M Y, g:i A')}";
        }
        $lines[] = "";
        $lines[] = "💳 *Pay here:*";
        $lines[] = $this->publicUrl();
        $lines[] = "";
        $lines[] = "_Powered by BookInStack_";
        return implode("\n", $lines);
    }
}