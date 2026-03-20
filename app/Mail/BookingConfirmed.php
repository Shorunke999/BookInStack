<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Developer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public array  $modeConfig;
    public string $qrCodeSvg;

    public function __construct(
        public Booking   $booking,
        public Developer $developer,
    ) {
        $this->modeConfig = $developer->modeConfig();
        $this->qrCodeSvg  = $this->generateQr($booking->reference);
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->modeConfig['mode']) {
            'ticket'      => "Your ticket is confirmed — {$this->booking->description}",
            'reservation' => "Reservation confirmed — {$this->booking->description}",
            default       => "Appointment confirmed — {$this->booking->description}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-confirmed');
    }

    private function generateQr(string $data): string
    {
        // Uses simplesoftwareio/simple-qrcode
        // Run: composer require simplesoftwareio/simple-qrcode
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(180)
                    ->margin(1)
                    ->generate($data);
            } catch (\Exception) {}
        }

        // Fallback — plain text reference if package not installed
        return '';
    }
}