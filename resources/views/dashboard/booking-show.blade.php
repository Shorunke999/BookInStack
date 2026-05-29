@extends('layouts.app')
@section('title', 'Booking — ' . $booking->reference)
@section('page-title', 'Booking Detail')

@section('content')

@if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;">✓ {{ session('success') }}</div>
@endif

{{-- Back + actions bar --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <a href="{{ route('dashboard.bookings') }}" style="font-size:13px;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:6px;">
        ← Back to Bookings
    </a>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if($booking->payment_status === 'paid' && !$booking->attended)
            <form method="POST" action="{{ route('bookings.attend', $booking->reference) }}" style="margin:0;">
                @csrf
                <input type="hidden" name="attended" value="1" />
                <button type="submit" class="btn btn-primary btn-sm">✓ Mark as Attended</button>
            </form>
        @elseif($booking->attended)
            <form method="POST" action="{{ route('bookings.attend', $booking->reference) }}" style="margin:0;"
                  onsubmit="return confirm('Undo attendance?')">
                @csrf
                <input type="hidden" name="attended" value="0" />
                <button type="submit" class="btn btn-outline btn-sm" style="color:var(--muted);">Undo Attendance</button>
            </form>
        @endif
    </div>
</div>

@php
    $statusColors = ['pending'=>['#d97706','#fffbeb'],'paid'=>['#15803d','#f0fdf4'],'failed'=>['#dc2626','#fef2f2'],'cancelled'=>['#6b7280','#f3f4f6'],'expired'=>['#6b7280','#f3f4f6']];
    [$statusColor, $statusBg] = $statusColors[$booking->payment_status] ?? ['#6b7280','#f3f4f6'];
    $cfg = $developer->modeConfig();
    $mode = $developer->booking_mode;
@endphp

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

    {{-- ── Left column ──────────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Status + reference header --}}
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-family:'DM Mono',monospace;font-size:18px;font-weight:700;letter-spacing:.04em;color:var(--ink);">
                        {{ $booking->reference }}
                    </div>
                    <div style="font-size:13px;color:var(--muted);margin-top:3px;">
                        Created {{ $booking->created_at->format('d M Y, g:i A') }}
                        @if($booking->booked_via)
                            · via <strong>{{ ucfirst(str_replace('_',' ',$booking->booked_via)) }}</strong>
                        @endif
                    </div>
                </div>
                <span style="font-size:13px;font-weight:700;padding:6px 16px;border-radius:20px;background:{{ $statusBg }};color:{{ $statusColor }};">
                    {{ ucfirst($booking->payment_status) }}
                </span>
            </div>

            @if($booking->paid_at)
                <div style="margin-top:14px;padding:12px 14px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;font-size:13px;color:#166534;display:flex;align-items:center;gap:8px;">
                    ✅ Payment confirmed {{ $booking->paid_at->format('d M Y, g:i A') }}
                </div>
            @endif
        </div>

        {{-- Customer info --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">Customer</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['👤','Name',  $booking->customer_name  ?? '—'],
                    ['📧','Email', $booking->customer_email],
                    ['📱','Phone', $booking->customer_phone ?? '—'],
                ] as [$icon,$label,$value])
                    <div style="display:flex;gap:12px;align-items:flex-start;font-size:13px;padding-bottom:10px;border-bottom:1px solid var(--border);">
                        <span>{{ $icon }}</span>
                        <span style="color:var(--muted);min-width:50px;">{{ $label }}</span>
                        <span style="font-weight:500;word-break:break-all;">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Booking details --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
                {{ match($mode) { 'ticket' => 'Ticket Details', 'reservation' => 'Reservation Details', default => 'Appointment Details' } }}
            </div>

            <div style="display:flex;flex-direction:column;gap:0;">
                @php
                    function detailRow($label, $value, $mono = false) {
                        if (!$value || $value === '—') return '';
                        $style = $mono ? 'font-family:monospace;font-size:12px;' : '';
                        return "<div style='display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;'>
                            <span style='color:#64748b;min-width:120px;flex-shrink:0;'>{$label}</span>
                            <span style='font-weight:500;{$style}'>{$value}</span>
                        </div>";
                    }
                @endphp

                {!! detailRow('Category', $booking->category?->name ?? $booking->description) !!}
                {!! detailRow('Amount', '₦' . number_format($booking->amount , 2)) !!}

                @if($mode === 'appointment')
                    {!! detailRow('Preferred Date', $booking->preferred_date?->format('l, d F Y')) !!}
                    {!! detailRow('Preferred Time', $booking->preferred_time ? \Carbon\Carbon::parse($booking->preferred_time)->format('g:i A') : null) !!}
                    {!! detailRow('Duration', $booking->category?->duration_minutes ? $booking->category->duration_minutes . ' min' : null) !!}
                @endif

                @if($mode === 'ticket')
                    {!! detailRow('Adults', $booking->adults . ' × ₦' . number_format($booking->amount , 2)) !!}
                    @if($booking->children > 0)
                        {!! detailRow('Children', $booking->children . ' child' . ($booking->children > 1 ? 'ren' : '')) !!}
                    @endif
                    {!! detailRow('Total Tickets', ($booking->adults + $booking->children) . ' ticket' . (($booking->adults + $booking->children) > 1 ? 's' : '')) !!}
                @endif

                @if($mode === 'reservation')
                    @php
                        $unit   = $developer->reservation_unit ?? 'night';
                        $nights = $booking->check_in && $booking->check_out
                            ? $booking->check_in->diffInDays($booking->check_out)
                            : null;
                    @endphp
                    {!! detailRow($unit === 'day' ? 'Start Date' : 'Check-in',  $booking->check_in?->format('d M Y')) !!}
                    {!! detailRow($unit === 'day' ? 'End Date' : 'Check-out',  $booking->check_out?->format('d M Y')) !!}
                    {!! detailRow('Duration', $nights ? $nights . ' ' . $unit . ($nights > 1 ? 's' : '') : null) !!}
                    {!! detailRow('Rate', '₦' . number_format($booking->amount , 2) . ' / ' . $unit) !!}
                    {!! detailRow('Total', $nights ? '₦' . number_format(($booking->amount * $nights) , 2) : null) !!}
                @endif

                {!! detailRow('Paystack Ref', $booking->paystack_reference, true) !!}

                @if($booking->payment_link_token)
                    {!! detailRow('Payment Link', $booking->payment_link_token, true) !!}
                @endif
            </div>
        </div>

        {{-- Metadata / notes --}}
        @if($booking->metadata && count($booking->metadata))
            <div class="card">
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Metadata</div>
                <pre style="background:var(--soft);border-radius:8px;padding:12px;font-size:12px;overflow-x:auto;color:var(--ink);">{{ json_encode($booking->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

    </div>

    {{-- ── Right column ─────────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Payment summary --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">Payment</div>
            @php
                $bookingAmount = $booking->amount;
                $feePct      = $developer->platform_fee_percent ?? 5;
                $platformFee = (int) round(($bookingAmount * $feePct) /100 );
                $devShare    = $bookingAmount - $platformFee;
            @endphp
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--muted);">Customer paid</span>
                <strong>₦{{ number_format($bookingAmount , 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="color:var(--muted);">Platform fee ({{ $feePct }}%)</span>
                <span style="color:#ef4444;">−₦{{ number_format($platformFee , 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;padding:10px 0;color:#15803d;">
                <span>Your earnings</span>
                <span>₦{{ number_format($devShare , 2) }}</span>
            </div>
        </div>

        {{-- Attendance --}}
        @if($booking->payment_status === 'paid')
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
                {{ $cfg['attendance_label'] ?? 'Attendance' }}
            </div>

            @if($booking->attended)
                <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;margin-bottom:12px;">
                    <span style="font-size:20px;">✅</span>
                    <div>
                        <div style="font-weight:600;font-size:13px;color:#166534;">Checked in</div>
                        @if($booking->attended_at)
                            <div style="font-size:12px;color:#15803d;">{{ $booking->attended_at->format('d M Y, g:i A') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Who marked attendance --}}
                @if($booking->attendedBy)
                    <div style="font-size:12px;color:var(--muted);margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                        👤 Marked by
                        <strong style="color:var(--ink);">{{ $booking->attendedBy->name }}</strong>
                        @if($booking->attendedBy->role === 'staff')<span style="background:#fef9c3;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;">Staff</span>@endif
                    </div>
                @endif

                @if($booking->attendance_note)
                    <div style="font-size:13px;color:var(--muted);padding:10px 14px;background:var(--soft);border-radius:8px;">
                        📝 {{ $booking->attendance_note }}
                    </div>
                @endif

            @else
                <div style="text-align:center;padding:16px;color:var(--muted);">
                    <div style="font-size:28px;margin-bottom:8px;">⏳</div>
                    <div style="font-size:13px;">Not yet checked in</div>
                </div>
            @endif
        </div>
        @endif

        {{-- Origin tracking --}}
        <div class="card">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">Origin</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;gap:10px;">
                    <span style="color:var(--muted);min-width:80px;">Source</span>
                    <span style="font-weight:600;">
                        @php
                            $viaLabels = [
                                'widget'        => ['🌐','Booking Widget'],
                                'payment_link'  => ['🔗','Payment Link'],
                                'dashboard'     => ['📊','Dashboard'],
                                'scan'          => ['📷','QR Scan'],
                            ];
                            [$viaIcon, $viaLabel] = $viaLabels[$booking->booked_via ?? 'widget'] ?? ['📋','Unknown'];
                        @endphp
                        {{ $viaIcon }} {{ $viaLabel }}
                    </span>
                </div>
                @if($booking->bookedBy)
                    <div style="display:flex;gap:10px;">
                        <span style="color:var(--muted);min-width:80px;">Booked by</span>
                        <span style="font-weight:600;">
                            {{ $booking->bookedBy->name }}
                            @if($booking->bookedBy->role === 'staff')
                                <span style="background:#fef9c3;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;margin-left:4px;">Staff</span>
                            @endif
                        </span>
                    </div>
                @else
                    <div style="display:flex;gap:10px;">
                        <span style="color:var(--muted);min-width:80px;">Booked by</span>
                        <span style="color:var(--muted);">Customer (self-service)</span>
                    </div>
                @endif
                 <div style="display:flex;gap:10px;">
                    <span style="color:var(--muted);min-width:80px;">Method</span>
                    <span>{{ strtoupper($booking->payment_method) }}</span>
                </div>
                <div style="display:flex;gap:10px;">
                    <span style="color:var(--muted);min-width:80px;">Account</span>
                    <span>{{ $developer->business_name ?? $developer->name }}</span>
                </div>
            </div>
        </div>

        {{-- QR Code (ticket mode) --}}
        @if($mode === 'ticket' && $booking->payment_status === 'paid')
        <div class="card" style="text-align:center;">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">QR Code</div>
            <div id="booking-qr" style="display:inline-block;padding:10px;border:1px solid var(--border);border-radius:10px;background:#fff;"></div>
            <div style="font-family:monospace;font-size:11px;color:var(--muted);margin-top:8px;">{{ $booking->reference }}</div>
        </div>
        @endif

       @if($booking->payment_status !== 'paid' && $booking->payment_link_token)

            @php
                $wa = preg_replace('/\D/', '', $booking->customer_phone ?? '');

                $text = urlencode(
                    "Hi {$booking->customer_name}, complete your payment here: "
                    . route('payment.link', $booking->payment_link_token)
                );

                $whatsappUrl = $wa
                    ? "https://wa.me/{$wa}?text={$text}"
                    : "https://wa.me/?text={$text}";
            @endphp

            <div class="card" style="text-align:center;">

                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
                    Payment
                </div>

                <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">

                    <a
                        href="{{ route('payment.link', $booking->payment_link_token) }}"
                        target="_blank"
                        class="btn btn-primary btn-sm"
                    >
                        Make Payment →
                    </a>

                    @if($booking->customer_phone)

                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            class="btn btn-outline btn-sm"
                            style="display:inline-flex;align-items:center;gap:7px;"
                        >
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="opacity:.7;flex-shrink:0;">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.554 4.112 1.523 5.84L.057 23.272a.75.75 0 0 0 .919.899l5.516-1.445A11.943 11.943 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.14-1.424l-.368-.218-3.813 1 1.024-3.741-.238-.382A9.955 9.955 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                            </svg>

                            Share via WhatsApp
                        </a>

                    @endif

                </div>

            </div>

        @endif

    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
@if($mode === 'ticket' && $booking->payment_status === 'paid')
  new QRCode(document.getElementById('booking-qr'), {
    text:         '{{ $booking->reference }}',
    width:        140,
    height:       140,
    colorDark:    '#0d0d14',
    colorLight:   '#ffffff',
    correctLevel: QRCode.CorrectLevel.M,
  });
@endif

// Mobile: stack columns
if (window.innerWidth < 768) {
    const grid = document.querySelector('[style*="grid-template-columns:1fr 340px"]');
    if (grid) grid.style.gridTemplateColumns = '1fr';
}
</script>
@endpush

@endsection
