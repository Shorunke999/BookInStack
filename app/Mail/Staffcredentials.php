<?php

namespace App\Mail;

use App\Models\Developer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCredentials extends Mailable 
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Developer $staff,
        public readonly string    $plainPassword,
        public readonly Developer $admin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->admin->business_name} staff account is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-credentials',
        );
    }
}