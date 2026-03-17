<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Developer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookingConfirmed extends Mailable 
{
    use Queueable, SerializesModels;

    public array $modeConfig;

    public function __construct(
        public Booking   $booking,
        public Developer $developer,
    ) {
        $this->modeConfig = $developer->modeConfig();
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
        return new Content(
            view: 'emails.booking-confirmed',
        );
    }

}
