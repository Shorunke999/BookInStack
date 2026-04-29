<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Developer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries   = 3;       // retry 3 times if it fails
    public int $timeout = 60;      // give up after 60s per attempt
    public int $backoff = 30; 

    
    public array  $modeConfig;
    public string $qrCodeSvg;
    public string $qrCodeBase64;

    public function __construct(
        public Booking   $booking,
        public Developer $developer,
    ) {
        $this->modeConfig   = $developer->modeConfig();
        $this->qrCodeSvg    = $this->generateQrSvg($booking->reference);
        $this->qrCodeBase64 = $this->generateQrBase64($booking->reference);
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

    // ── PDF attachment — ticket mode only ─────────────────────────────────────

    public function attachments(): array
    {
        if ($this->modeConfig['mode'] !== 'ticket') {
            return [];
        }

        try {
            $pdfContent = $this->generateTicketPdf();
            if (!$pdfContent) return [];

            $filename = 'ticket-' . strtolower($this->booking->reference) . '.pdf';

            return [
                Attachment::fromData(fn () => $pdfContent, $filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Ticket PDF generation failed: ' . $e->getMessage()
            );
            return [];
        }
    }

    // ── PDF generation via barryvdh/laravel-dompdf ────────────────────────────
    // Install: composer require barryvdh/laravel-dompdf

    private function generateTicketPdf(): ?string
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        $booking    = $this->booking;
        $developer  = $this->developer;
        $modeConfig = $this->modeConfig;
        $qrBase64   = $this->qrCodeBase64;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'emails.ticket-pdf',
            compact('booking', 'developer', 'modeConfig', 'qrBase64')
        );

        // ~140mm × 240mm — standard ticket size
        $pdf->setPaper([0, 0, 400, 680], 'portrait');

        return $pdf->output();
    }

    // ── QR helpers ────────────────────────────────────────────────────────────

    private function generateQrSvg(string $data): string
    {
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(180)->margin(1)->generate($data);
            } catch (\Exception) {}
        }
        return '';
    }

    private function generateQrBase64(string $data): string
    {
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(200)->margin(1)->generate($data);
                return 'data:image/png;base64,' . base64_encode($png);
            } catch (\Exception) {}
        }
        return '';
    }
}